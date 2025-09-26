<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\FiscalInformationsType;
use App\Form\PreferencesDevisType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

}
