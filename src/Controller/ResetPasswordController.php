<?php

namespace App\Controller;

use App\Form\ResetPasswordProcessType;
use App\Form\ResetPasswordType;
use App\Service\TokenPasswordService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ResetPasswordController extends AbstractController
{
    public function __construct(private readonly TokenPasswordService $tokenPasswordService)
    {
    }

    #[Route('/reset_password', name: 'app_reset_password_process')]
    public function reinitPassword(Request $request): Response
    {
        $message = null;

        $form = $this->createForm(ResetPasswordProcessType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->tokenPasswordService->sendPasswordReset($form->get('email')->getData());

            if ($result) {
                $message = "Un email vous a été envoyé avec un lien pour réinitialiser votre mot de passe.";
            } else {
                $message = "Une erreur est survenue, merci de contacter le support";
            }
        }

        return $this->render('reset_password/reinit.html.twig', [
            'form' => $form->createView(),
            'message' => $message
        ]);
    }

    #[Route('/reset_password/{token}', name: 'app_reset_password')]
    public function index(string $token, Request $request): Response
    {
        $message = null;

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = $this->tokenPasswordService->resetPassword($token, $form->get('password')->getData());

            if ($result) {
                $message = "Mot de passe défini, veuillez vous connecter. <a href='/login' class='underline'>Se connecter</a>";
            } else {
                $message = "Une erreur est survenue, merci de contacter le support";
            }
        }

        return $this->render('reset_password/index.html.twig', [
            'form' => $form->createView(),
            'message' => $message
        ]);
    }
}
