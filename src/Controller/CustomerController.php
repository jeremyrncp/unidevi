<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Form\CustomerType;
use App\Repository\CustomerRepository;
use App\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerController extends AbstractController
{
    #[Route('/customer', name: 'app_customer')]
    public function index(CustomerRepository $customerRepository, Request $request, PaginatorInterface $paginator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $customers = $customerRepository->findBy(["owner" => $user], ["id" => "DESC"]);

        $pagination = $paginator->paginate(
            $customers,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('customer/index.html.twig', [
            'customers' => $pagination,
            'user' => $user
        ]);
    }

    #[Route('/customer/add', name: 'app_customer_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $customer = new Customer();
        $customer->setOwner($user);

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($customer);
            $entityManager->flush();

            $this->addFlash("message", "Client bien ajouté");

            return $this->redirectToRoute("app_customer");
        }

        return $this->render('customer/add.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/customer/{id}/edit', name: 'app_customer_edit')]
    public function edit(Customer $customer, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user !== $customer->getOwner()) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash("message", "Client bien modifié");

            return $this->redirectToRoute("app_customer");
        }

        return $this->render('customer/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/customer/{id}/delete', name: 'app_customer_delete', methods: ['POST'])]
    public function delete(Customer $customer, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$customer->getId(), $request->getPayload()->getString('_token'))) {
            /** @var User $user */
            $user = $this->getUser();

            if ($user !== $customer->getOwner()) {
                throw new AccessDeniedHttpException();
            }

            $entityManager->remove($customer);
            $entityManager->flush();

            $this->addFlash("message", "Client bien supprimé");
        }

        return $this->redirectToRoute('app_customer', [], Response::HTTP_SEE_OTHER);
    }

}
