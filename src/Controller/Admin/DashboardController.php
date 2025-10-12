<?php

namespace App\Controller\Admin;

use App\Entity\Appointment;
use App\Entity\Holiday;
use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\TemplatedEmail;
use App\Entity\Timeslot;
use App\Entity\User;
use App\Form\DateRangeType;
use App\Repository\AppointmentRepository;
use App\Repository\PaymentRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;
use App\Service\AppointmentService;
use App\VO\DateRangeVO;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UserRepository $userRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {
    }

    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        $daterangeVO = new DateRangeVO();

        $daterangeVO->end = new \DateTime();
        $daterangeVO->start = (new \DateTime())->modify("-3 months");

        $form = $this->createForm(DateRangeType::class, $daterangeVO);
        $form->handleRequest($this->requestStack->getCurrentRequest());

        /** Payments */
        $payments = $this->paymentRepository->findBetwennDatesAndState($daterangeVO->start, $daterangeVO->end, Payment::STATE_ACCEPTED);

        $sumCA = 0;
        $CAPaymentByMONTH = [];

        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $sumCA += $payment->getAmountCents();

            if (!array_key_exists($payment->getCreatedAt()->format("m-Y"), $CAPaymentByMONTH)) {
                $CAPaymentByMONTH[$payment->getCreatedAt()->format("m-Y")] = 0;
            }

            $CAPaymentByMONTH[$payment->getCreatedAt()->format("m-Y")] += $payment->getAmountCents();
        }



        /** Subscriptions by period */
        $subscriptions = $this->subscriptionRepository->findByRangeDates($daterangeVO->start, $daterangeVO->end);

        $nbSubscriptionsactive = 0;
        $nbSubscriptionsInactive = 0;

        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            if ($subscription->isActive() === true) {
                $nbSubscriptionsactive ++;
            } else {
                $nbSubscriptionsInactive ++;
            }
        }


        /** Subscriptions total */
        $subscriptions = $this->subscriptionRepository->findAll();

        $nbSubscriptionsactiveTotal = 0;
        $nbSubscriptionsInactiveTotal = 0;

        /** @var Subscription $subscription */
        foreach ($subscriptions as $subscription) {
            if ($subscription->isActive() === true) {
                $nbSubscriptionsactiveTotal ++;
            } else {
                $nbSubscriptionsInactiveTotal ++;
            }
        }



        return $this->render('admin/dashboard_initial.html.twig', [
            "countUsers" => $this->userRepository->count(),
            "form" => $form->createView(),
            "sumCA" => $sumCA,
            "numberPayments" => count($payments),
            "rangeDates" => ["start" => $daterangeVO->start, "end" => $daterangeVO->end],
            "nbSubscriptionsactive" => $nbSubscriptionsactive,
            "nbSubscriptionsInactive" => $nbSubscriptionsInactive,
            "nbSubscriptionsactiveTotal" => $nbSubscriptionsactiveTotal,
            "nbSubscriptionsInactiveTotal" => $nbSubscriptionsInactiveTotal
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Unidevi');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-list', User::class);
        yield MenuItem::linkToCrud('Souscriptions', 'fas fa-list', Subscription::class);
        yield MenuItem::linkToCrud('Paiements', 'fas fa-list', Payment::class);
    }
}
