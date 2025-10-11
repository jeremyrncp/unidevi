<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\PaymentRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Payout;
use Stripe\Service\PayoutService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SubscriptionController extends AbstractController
{
    #[Route('/parameters/subscription', name: 'app_parameters_subscription')]
    public function index(SubscriptionRepository $subscriptionRepository, PaymentRepository $paymentRepository): Response
    {
        $priceSubscription = null;

        /** @var User $user */
        $user = $this->getUser();

        $subscription = $subscriptionRepository->findOneBy(["owner" => $user]);
        $payments = $paymentRepository->findBy(["owner" => $user], ["createdAt" => "DESC"]);
        $subscriptionStripe = null;

        if ($subscription instanceof Subscription) {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            $subscriptionStripe = \Stripe\Subscription::retrieve($subscription->getSubscriptionStripeId());
            $invoiceStripe = \Stripe\Invoice::retrieve($subscriptionStripe->latest_invoice);

            $priceSubscription = $invoiceStripe->amount_due;
        }

        return $this->render('parameters/subscription.html.twig', [
            'user' => $user,
            'subscription' => $subscription,
            'payments' => $payments,
            'subscriptionStripe' => $subscriptionStripe,
            'priceSubscription' => $priceSubscription
        ]);
    }


    #[Route('/create-subscription', name: 'create_subscription', methods: ['POST'])]
    public function createSubscription(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $data = json_decode($request->getContent(), true);
        $email = $data['email'];
        $paymentMethod = $data['paymentMethod'];

        if (array_key_exists("promoCode", $data) && $data['promoCode'] !== "") {
            $promoCode = $data['promoCode'];

            // 2. Récupérer l’ID du code promo (s’il existe)
            $promo = \Stripe\PromotionCode::all([
                'code' => $promoCode,
                'active' => true,
                'limit' => 1
            ]);

            if (empty($promo->data)) {
                return new JsonResponse(['message' => 'Code promo invalide'], 400);
            }

            $promoCodeId = $promo->data[0]->id;
        }

        // 1. Créer un client
        $customer = \Stripe\Customer::create([
            'preferred_locales' => ['fr'],
            'email' => $email,
            'payment_method' => $paymentMethod,
            'invoice_settings' => [
                'default_payment_method' => $paymentMethod,
            ],
        ]);

        if (isset($promoCodeId)) {
            // 2. Créer un abonnement
            $subscriptionStripe = \Stripe\Subscription::create([
                'customer' => $customer->id,
                'items' => [[ 'price' => $_ENV['STRIPE_PRICE_ID']]],
                'payment_behavior' => 'default_incomplete',
                'default_payment_method' => $paymentMethod,
                'discounts' => [["promotion_code" => $promoCodeId]],
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'billing_mode' => ["type" => 'flexible'],
                'expand' => ['latest_invoice.confirmation_secret']
            ]);
        } else {
            // 2. Créer un abonnement
            $subscriptionStripe = \Stripe\Subscription::create([
                'customer' => $customer->id,
                'items' => [[ 'price' => $_ENV['STRIPE_PRICE_ID']]],
                'payment_behavior' => 'default_incomplete',
                'default_payment_method' => $paymentMethod,
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'billing_mode' => ["type" => 'flexible'],
                'expand' => ['latest_invoice.confirmation_secret']
            ]);
        }

        $subscription = new Subscription();
        $subscription->setCreatedAt(new \DateTime())
                     ->setOwner($user)
                     ->setSubscriptionStripeId($subscriptionStripe->id)
                     ->setActive(true);

        $entityManager->persist($subscription);


        $entityManager->flush();


        $confirmationSecret = $subscriptionStripe->latest_invoice->confirmation_secret;


        return $this->json([
            'subscriptionId' => $subscriptionStripe->id,
            'clientSecret' =>  $confirmationSecret->client_secret,
        ]);
    }

    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function handleStripeWebhook(Request $request, LoggerInterface $logger, SubscriptionRepository $subscriptionRepository, PaymentRepository $paymentRepository, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );

            $logger->info('✅ Webhook Stripe reçu : '.$event->type);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    /** @var PaymentIntent $paymentIntent */
                    $paymentIntent = $event->data->object;

                    $payment = $paymentRepository->findOneBy(["stripeId" => $paymentIntent->id]);

                    if ($payment === null) {
                        /** @var Customer $customer */
                        $customer = \Stripe\Customer::retrieve($paymentIntent->customer);

                        $user = $userRepository->findOneBy(["email" => $customer->email]);
                        $payment = new Payment();
                        $payment->setCreatedAt(new \DateTime())
                            ->setState(Payment::STATE_ACCEPTED)
                            ->setStripeId($paymentIntent->id)
                            ->setEventType($event->type)
                            ->setAmountCents($paymentIntent->amount)
                            ->setOwner($user);
                        $entityManager->persist($payment);
                        $entityManager->flush();
                    }
                break;

                case 'payment_intent.canceled':
                    /** @var PaymentIntent $paymentIntent */
                    $paymentIntent = $event->data->object;

                    $payment = $paymentRepository->findOneBy(["stripeId" => $paymentIntent->id]);

                    if ($payment === null) {
                        /** @var Customer $customer */
                        $customer = \Stripe\Customer::retrieve($paymentIntent->customer);

                        $user = $userRepository->findOneBy(["email" => $customer->email]);

                        if ($user instanceof User) {
                            /** @var Subscription $subscription */
                            $subscription = $subscriptionRepository->findOneBy(["owner" => $user]);

                            if ($subscription instanceof Subscription) {
                                $subscription->setActive(false);

                                $payment = new Payment();
                                $payment->setCreatedAt(new \DateTime())
                                    ->setState(Payment::STATE_REJECTED)
                                    ->setStripeId($paymentIntent->id)
                                    ->setEventType($event->type)
                                    ->setAmountCents($paymentIntent->amount)
                                    ->setOwner($user);

                                $entityManager->persist($payment);
                                $entityManager->flush();
                            }
                        }
                    }
                break;

                case 'payment_intent.payment_failed':
                    /** @var PaymentIntent $paymentIntent */
                    $paymentIntent = $event->data->object;

                    $payment = $paymentRepository->findOneBy(["stripeId" => $paymentIntent->id]);

                    if ($payment === null) {
                        /** @var Customer $customer */
                        $customer = \Stripe\Customer::retrieve($paymentIntent->customer);

                        $user = $userRepository->findOneBy(["email" => $customer->email]);
                        $payment = new Payment();
                        $payment->setCreatedAt(new \DateTime())
                            ->setState(Payment::STATE_REJECTED)
                            ->setStripeId($paymentIntent->id)
                            ->setEventType($event->type)
                            ->setAmountCents($paymentIntent->amount)
                            ->setOwner($user);
                        $entityManager->persist($payment);
                        $entityManager->flush();
                    }
                break;

                case 'invoice.paid':
                    /** @var Invoice $invoice */
                    $invoice = $event->data->object;

                    /** @var Customer $customer */
                    $customer = \Stripe\Customer::retrieve($invoice->customer);
                    $user = $userRepository->findOneBy(["email" => $customer->email]);
                    $paymentIntent  = \Stripe\PaymentIntent::retrieve($invoice->payments->id);

                    $payment = $paymentRepository->findOneBy(["stripeId" => $paymentIntent->id]);

                    if ($payment === null) {
                        $payment = new Payment();
                        $payment->setCreatedAt(new \DateTime())
                            ->setState(Payment::STATE_ACCEPTED)
                            ->setStripeId($paymentIntent->id)
                            ->setEventType($event->type)
                            ->setAmountCents($invoice->amount_paid)
                            ->setOwner($user);
                        $entityManager->persist($payment);
                        $entityManager->flush();
                    }
                break;

                case 'invoice.payment_failed':
                    /** @var Invoice $invoice */
                    $invoice = $event->data->object;

                    /** @var Customer $customer */
                    $customer = \Stripe\Customer::retrieve($invoice->customer);
                    $user = $userRepository->findOneBy(["email" => $customer->email]);
                    $paymentIntent  = \Stripe\PaymentIntent::retrieve($invoice->payments->id);

                    $payment = $paymentRepository->findOneBy(["stripeId" => $paymentIntent->id]);

                    if ($payment === null) {
                        $payment = new Payment();
                        $payment->setCreatedAt(new \DateTime())
                            ->setState(Payment::STATE_REJECTED)
                            ->setStripeId($paymentIntent->id)
                            ->setEventType($event->type)
                            ->setAmountCents($invoice->amount_paid)
                            ->setOwner($user);
                        $entityManager->persist($payment);
                        $entityManager->flush();
                    }
                    break;

                case 'customer.subscription.deleted	':
                    /** @var \Stripe\Subscription $subscription */
                    $subscriptionStripe = $event->data->object;

                    /** @var Subscription $subscription */
                    $subscription = $subscriptionRepository->findOneBy(["subscriptionStripeId" => $subscriptionStripe->id]);
                    $entityManager->remove($subscription);
                    $entityManager->flush();
                    break;


                case 'customer.subscription.paused':
                    /** @var \Stripe\Subscription $subscription */
                    $subscriptionStripe = $event->data->object;

                    /** @var Subscription $subscription */
                    $subscription = $subscriptionRepository->findOneBy(["subscriptionStripeId" => $subscriptionStripe->id]);
                    $subscription->setActive(false);
                    $entityManager->flush();
                    break;

                case 'customer.subscription.resumed':
                    /** @var \Stripe\Subscription $subscription */
                    $subscriptionStripe = $event->data->object;

                    /** @var Subscription $subscription */
                    $subscription = $subscriptionRepository->findOneBy(["subscriptionStripeId" => $subscriptionStripe->id]);
                    $subscription->setActive(true);
                    $entityManager->flush();
                    break;

                case 'payout.paid':
                    /** @var Payout` $payout */
                    $payout = $event->data->object;
                    $payment = new Payment();
                        $payment->setCreatedAt(new \DateTime())
                            ->setState(Payment::STATE_ACCEPTED)
                            ->setEventType($event->type)
                            ->setAmountCents($payout->amount)
                            ->setOwner($userRepository->findOneBy(["email" => $payout->recipient_email]));
                        $entityManager->persist($payment);
                        $entityManager->flush();
                    break;
                default:
                    $logger->info('ℹ️ Événement ignoré : '.$event->type);
            }

            return new Response('Webhook traité', 200);

        } catch (\UnexpectedValueException $e) {
            return new Response('⚠️ Payload invalide', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('⚠️ Signature invalide', 400);
        }
    }

    #[Route('/subscription/trial', name: 'app_subscription_trial')]
    public function trial(EntityManagerInterface $entityManager)
    {
        /** @var User $user */
        $user = $this->getUser();

        if (is_null($user->getTrialEndedAt())) {
            $user->setTrialEndedAt((new \DateTime())->modify("+3 days"));
            $entityManager->flush();

            $this->addFlash("message", "Vous bénéficiez désormais de l'offre d'essai gratuit");
            return $this->redirectToRoute("app_subscription");
        }

        $this->addFlash("error", "Vous avez déjà bénéficié de l'offre d'essai gratuit");
        return $this->redirectToRoute("app_subscription");
    }

    #[Route('/subscription/pause', name: 'app_subscription_pause')]
    public function pause(EntityManagerInterface $entityManager, SubscriptionRepository $subscriptionRepository)
    {
        /** @var User $user */
        $user = $this->getUser();

        $subscription = $subscriptionRepository->findOneBy(["owner" => $user]);

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        \Stripe\Subscription::update($subscription->getSubscriptionStripeId(), [
            'pause_collection' => [
                'behavior' => 'mark_uncollectible', // ou 'keep_as_draft' ou 'void'
            ]
        ]);

        $subscription->setActive(false);
        $entityManager->flush();

        $this->addFlash("message", "Abonnement mis en pause");

        return $this->redirectToRoute("app_subscription");
    }

    #[Route('/subscription/resume', name: 'app_subscription_resume')]
    public function resume(EntityManagerInterface $entityManager, SubscriptionRepository $subscriptionRepository)
    {
        /** @var User $user */
        $user = $this->getUser();

        $subscription = $subscriptionRepository->findOneBy(["owner" => $user]);

        \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        \Stripe\Subscription::update($subscription->getSubscriptionStripeId(), [
            'pause_collection' => ''
        ]);

        $subscription->setActive(true);
        $entityManager->flush();


        $this->addFlash("message", "Abonnement réactivé");

        return $this->redirectToRoute("app_subscription");
    }
}
