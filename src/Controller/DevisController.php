<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Devis;
use App\Entity\User;
use App\Form\CustomerDevisType;
use App\Form\CustomerType;
use App\Form\SelectionCustomerType;
use App\Repository\CustomerRepository;
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
    public function step0(Request $request, EntityManagerInterface $entityManager, CustomerRepository $customerRepository): Response
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

            return $this->redirectToRoute("app_devis_step1", ["id" => $devis->getId()]);
        }

        return $this->render('devis/index.html.twig', [
            'user' => $user,
            'customer' => $request->query->get('customer')
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
            $devis->setCustomer($customer);
            $customer->setOwner($user);
            $entityManager->persist($customer);

            $this->hydrateDevisWithCustomer($customer, $user, $devis);

            $entityManager->flush();

            return $this->redirectToRoute("app_devis_step2", ["id" => $devis->getId()]);
        } else if ($selectionCustomerForm->isSubmitted() && $selectionCustomerForm->isValid()) {
            $customer = $selectionCustomerVO->customer;
            $customer->setOwner($user);
            $this->hydrateDevisWithCustomer($customer, $user, $devis);
            $entityManager->flush();

            return $this->redirectToRoute("app_devis_step2", ["id" => $devis->getId()]);
        }

        return $this->render('devis/step1.html.twig', [
            'user' => $user,
            "selectionCustomerForm" => $selectionCustomerForm->createView(),
            "customerForm" => $customerForm->createView()
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
            'user' => $user
        ]);
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
                ->setSiretCustomer($customer->getSiret());
        }
    }
}