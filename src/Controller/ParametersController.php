<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\Service;
use App\Entity\User;
use App\Form\CustomerType;
use App\Form\FiscalInformationsType;
use App\Form\PreferencesDevisType;
use App\Form\PreferencesNumerotationType;
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ParametersController extends AbstractController
{
    #[Route('/parameters/info-entreprise', name: 'app_parameters_info_entreprise')]
    public function infoEntreprise(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $infoEntrepriseForm = $this->createForm(FiscalInformationsType::class, $user);
        $infoEntrepriseForm->handleRequest($request);

        if ($infoEntrepriseForm->isSubmitted() && $infoEntrepriseForm->isValid()) {
            $entityManager->flush();

            $this->addFlash("message", "Informations fiscales mises à jour");
        }

        return $this->render('parameters/index.html.twig', [
            'user' => $user,
            'form' => $infoEntrepriseForm->createView()
        ]);
    }

    #[Route('/parameters/preferences-devis', name: 'app_parameters_preferences_devis')]
    public function preferencesDevis(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $preferencesDevisForm = $this->createForm(PreferencesDevisType::class, $user);
        $preferencesDevisForm->handleRequest($request);

        if ($preferencesDevisForm->isSubmitted() && $preferencesDevisForm->isValid()) {
            $entityManager->flush();

            $this->addFlash("message", "Préférences devis mises à jour");
        }

        return $this->render('parameters/devis.html.twig', [
            'user' => $user,
            'form' => $preferencesDevisForm->createView()
        ]);
    }

    #[Route('/parameters/styles', name: 'app_parameters_styles')]
    public function styles(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('parameters/styles.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/parameters/numerotation', name: 'app_parameters_numerotation')]
    public function numerotation(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(PreferencesNumerotationType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash("message", "Préférences de numérotation mises à jour");
        }

        return $this->render('parameters/numerotation.html.twig', [
            'user' => $user,
            "form" => $form->createView()
        ]);
    }

    #[Route('/parameters/services', name: 'app_parameters_services')]
    public function services(Request $request, ServiceRepository $serviceRepository, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $services = $serviceRepository->findBy(["owner" => $user]);

        return $this->render('parameters/services/index.html.twig', [
            'user' => $user,
            "services" => $services
        ]);
    }

    #[Route('/parameters/services/add', name: 'app_service_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $service = new Service();
        $service->setOwner($user);

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash("message", "Service bien ajouté");

            return $this->redirectToRoute("app_parameters_services");
        }

        return $this->render('parameters/services/add.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/parameters/services/{id}/edit', name: 'app_service_edit')]
    public function edit(Service $service, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user !== $service->getOwner()) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash("message", "Service bien modifié");

            return $this->redirectToRoute("app_parameters_services");
        }

        return $this->render('parameters/services/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/parameters/services/{id}/delete', name: 'app_service_delete', methods: ['POST'])]
    public function delete(Service $service, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$service->getId(), $request->getPayload()->getString('_token'))) {
            /** @var User $user */
            $user = $this->getUser();

            if ($user !== $service->getOwner()) {
                throw new AccessDeniedHttpException();
            }

            $entityManager->remove($service);
            $entityManager->flush();

            $this->addFlash("message", "Service bien supprimé");
        }

        return $this->redirectToRoute('app_parameters_services', [], Response::HTTP_SEE_OTHER);
    }

}
