<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Customer;
use App\Entity\Devis;
use App\Entity\Upsell;
use App\Entity\User;
use App\Form\CustomerDevisType;
use App\Form\SelectionCustomerType;
use App\Repository\CustomerRepository;
use App\Service\NumerotationService;
use App\VO\SelectionCustomerVO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class DevisController extends AbstractController
{
    #[Route('/devis/step0', name: 'app_devis')]
    public function step0(Request $request, EntityManagerInterface $entityManager, CustomerRepository $customerRepository, NumerotationService $numerotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->request->get("font") !== null) {
            $style           = $request->request->get("style");
            $font            = $request->request->get("font");
            $colorAccent     = $request->request->get("colorAccent");
            $rememberDefault = $request->request->get("rememberDefault");
            $customer = $request->request->get("customer");

            if ($rememberDefault !== null) {
                $user->setStyleType($style)->setStylePolice($font)->setStyleColor($colorAccent);

                $entityManager->flush();
            }

            $devis = new Devis();
            $devis->setOwner($user);
            $devis->setStyle($style);
            $devis->setColor($colorAccent);
            $devis->setFont($font);
            $devis->setNumber($numerotationService->getNumberDevis($user));

            if ($user->getCompanyName() !== null) {
                $devis->setNameCompany($user->getCompanyName());
            }

            if ($user->getPhoneNumber() !== null) {
                $devis->setPhoneNumberCompany($user->getPhoneNumber());
            }

            if ($user->getEmail() !== null) {
                $devis->setEmailCompany($user->getEmail());
            }

            if ($user->getSiret() !== null) {
                $devis->setSiretCompany($user->getSiret());
            }

            if ($user->getAdresse() !== null) {
                $devis->setAddress($user->getAdresse());
            }

            if ($user->getPostalCode() !== null) {
                $devis->setPostalCodeCompany($user->getPostalCode());
            }

            if ($user->getCountry() !== null) {
                $devis->setCountryCompany($user->getCountry());
            }

            if (is_int($customer)) {
                /** @var Customer $customerEntity */
                $customerEntity = $customerRepository->find($customer);

                if ($customerEntity->getOwner() === $user) {
                    $devis->setCustomer($customerEntity);
                    $devis->setNameCustomer($customerEntity->getName())
                          ->setAddressCustomer($customerEntity->getAddress())
                          ->setPostalCodeCustomer($customerEntity->getPostalCode())
                          ->setCityCustomer($customerEntity->getCity())
                          ->setSiretCustomer($customerEntity->getSiret());
                }
            }


            $entityManager->persist($devis);
            $entityManager->flush();

            if (is_int($customer)) {
                return $this->redirectToRoute("app_devis_step2", ["id" => $devis->getId()]);
            }

            return $this->redirectToRoute("app_devis_step1", ["id" => $devis->getId()]);
        }

        return $this->render('devis/index.html.twig', [
            'user' => $user,
            'customer' => $request->query->get('customer'),
        ]);
    }

    #[Route('/devis/step1/{id}', name: 'app_devis_step1')]
    public function step1(Devis $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire du devis");
        }

        $selectionCustomerVO = new SelectionCustomerVO();
        $selectionCustomerForm = $this->createForm(SelectionCustomerType::class, $selectionCustomerVO);
        $selectionCustomerForm->handleRequest($request);

        $customer = new Customer();
        $customerForm = $this->createForm(CustomerDevisType::class, $customer);
        $customerForm->handleRequest($request);

        if ($customerForm->isSubmitted() && $customerForm->isValid()) {
            $entityManager->persist($customer);

            $devis->setCustomer($customer);
            $customer->setOwner($user);
            $this->hydrateDevisWithCustomer($customer, $user, $devis);

            $entityManager->flush();

            return $this->redirectToRoute("app_devis_step2", ["id" => $devis->getId()]);
        } else if ($selectionCustomerForm->isSubmitted() && $selectionCustomerForm->isValid()) {
            $customer = $selectionCustomerVO->customer;
            $customer->setOwner($user);
            $devis->setCustomer($customer);
            $this->hydrateDevisWithCustomer($customer, $user, $devis);
            $entityManager->flush();

            return $this->redirectToRoute("app_devis_step2", ["id" => $devis->getId()]);
        }

        return $this->render('devis/step1.html.twig', [
            'user' => $user,
            "selectionCustomerForm" => $selectionCustomerForm->createView(),
            "customerForm" => $customerForm->createView(),
        ]);
    }

    #[Route('/devis/step2/{id}', name: 'app_devis_step2')]
    public function step2(Devis $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire du devis");
        }

        return $this->render('devis/step2.html.twig', [
            'user' => $user,
            'devis' => $devis,
        ]);
    }

    #[Route('/devis/step3-manuel/{id}', name: 'app_devis_step3_manuel')]
    public function step3Manuel(Devis $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire du devis");
        }

        if ($request->request->get("submitType") === "modeManuel") {
            $devis->setName($request->request->get("titleDevis"));
            $devis->setTvaRate($request->request->get("tvaRate"));


            $data = $this->mapServiceAndUpsell($request);

            foreach ($data as $uniquid => $item) {
                if ($item["type"] === "service") {
                    $article = new Article();
                    $article->setName($item["title"])
                            ->setPrice($item["price"] * 100)
                            ->setDescription($item["description"]);

                    $devis->addArticle($article);
                } else if ($item["type"] === "upsell") {
                    $upsell = new Upsell();
                    $upsell->setName($item["title"])
                        ->setPrice($item["price"] * 100)
                        ->setDescription($item["description"]);

                    $devis->addUpsell($upsell);
                }
            }

            $entityManager->flush();
            $this->addFlash("message", "Services et upsells bien ajoutés");

            return $this->redirectToRoute("app_devis_step4", ["id" => $devis->getId()]);
        }

        return $this->render('devis/step3_manuel.html.twig', [
            'user' => $user,
        ]);
    }


    #[Route('/devis/step4/{id}', name: 'app_devis_step4')]
    public function step4(Devis $devis, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire du devis");
        }

        return $this->render('devis/step4.html.twig', array_merge([
            'user' => $user,
            'devis' => $devis,
        ], $this->getsumAndOtherElements($devis)));
    }

    #[Route('/devis/duplication/{id}', name: 'app_devis_duplication')]
    public function duplication(Devis $devis, Request $request, EntityManagerInterface $entityManager, NumerotationService $numerotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire du devis");
        }

        $cloneDevis = clone $devis;
        $cloneDevis->setNumber($numerotationService->getNumberDevis($user));
        $entityManager->persist($cloneDevis);
        $entityManager->flush();

        $this->addFlash("message", "Devis dupliqué");

        return $this->render('devis/step4.html.twig', array_merge([
            'user' => $user,
            'devis' => $cloneDevis,
        ], $this->getsumAndOtherElements($cloneDevis)));
    }

    #[Route('/devis/archiver/{id}', name: 'app_devis_archiver')]
    public function archiver(Devis $devis, Request $request, EntityManagerInterface $entityManager, NumerotationService $numerotationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($devis->getOwner() !== $user) {
            throw new UnauthorizedHttpException("Non propritaire du devis");
        }

        $devis->setArchivedAt(new \DateTime());
        $entityManager->flush();

        $this->addFlash("message", "Devis archivé");

        return $this->render('devis/step4.html.twig', array_merge([
            'user' => $user,
            'devis' => $devis,
        ], $this->getsumAndOtherElements($devis)));
    }


    private function getsumAndOtherElements(Devis $devis): array
    {
        $sumArticles = 0;
        $sumUpsells = 0;

        /** @var Article $article */
        foreach ($devis->getArticles() as $article) {
            $sumArticles += $article->getPrice();
        }


        /** @var Upsell $upsell */
        foreach ($devis->getUpsells() as $upsell) {
            $sumUpsells += $upsell->getPrice();
        }

        return [
          "sumUpsells" => $sumUpsells/100,
          "sumArticles" => $sumArticles/100,
          "tvaUpsells" => $sumUpsells/100*($devis->getTvaRate()/100),
          "tvaArticles" => $sumArticles/100*($devis->getTvaRate()/100),
          "tvaTotal" => ($sumUpsells+$sumArticles)/100*($devis->getTvaRate()/100)
        ];
    }



    /**
     * Description mapServiceAndUpsell function
     *
     * @param Request $request
     *
     * @return array
     */
    private function mapServiceAndUpsell(Request $request)
    {
        $return = [];

        foreach ($request->request->all() as $key => $value) {
            $explode = explode("-", $key);

            if (count($explode) === 2) {
                $typeData = $explode[0];
                $uniquid = $explode[1];

                if (!array_key_exists($uniquid, $return)) {
                    $return[$uniquid] = [];
                }

                $return[$uniquid][$typeData] = $value;
            }
        }


        foreach ($return as $key => $item) {
            if (!array_key_exists("checkbox", $item) && $item["checkbox"] !== "on") {
                unset($return[$key]);
            }
        }

        return $return;
    }

    /**
     * Description extracted function
     *
     * @param Customer  $customer
     * @param User|null $user
     * @param Devis     $devis
     *
     * @return void
     */
    private function hydrateDevisWithCustomer(Customer $customer, ?User $user, Devis $devis): void
    {
        if ($customer->getOwner() === $user) {
            $devis->setNameCustomer($customer->getName())
                ->setAddressCustomer($customer->getAddress())
                ->setPostalCodeCustomer($customer->getPostalCode())
                ->setCityCustomer($customer->getCity())
                ->setSiretCustomer($customer->getSiret())
                ->setMentionsLegales($user->getMentionsLegalesDevis());
        }
    }
}