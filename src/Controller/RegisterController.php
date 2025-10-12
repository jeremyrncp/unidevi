<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\RecaptchaVerifier;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function index(Request $request, RecaptchaVerifier $captcha, Security $security, UserRepository $userRepository, NotificationService $notificationService, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $errors = [];

        if ($request->request->get("email") === "") {
            $errors[] = "L'adresse email est requise";
        }

        if ($request->request->get("fullname") === "") {
            $errors[] = "Le nom complet est requis";
        }

        if ($request->request->get("nameCompany") === "") {
            $errors[] = "Le nom de la société est requis";
        }

        if ($request->request->get("phone") === "") {
            $errors[] = "Le téléphone est requis";
        }


        if ($request->request->get("password") === "") {
            $errors[] = "Le mot de passe est requis";
        }

        if (count($errors) === 0 && $request->request->get("email") !== null) {
            $token = $request->request->get('g-recaptcha-response') ?: $request->request->get('recaptcha_token', '');
            $remoteIp = $request->getClientIp();

            // Pour v3, préciser l’action si tu l’utilises côté front (voir plus bas)
            $isValid = $captcha->verify($token, $remoteIp, $expectedAction = 'contact_submit');

            if (!$isValid) {
                $this->addFlash('error', 'Échec reCAPTCHA. Réessaie.');
            } else {
                $uerFinded = $userRepository->findOneBy(["email" => $request->request->get("email")]);

                if ($uerFinded instanceof User) {
                    $this->addFlash("error", "Cet email est déjà pris,  <a href='/login'>connectez-vous</a>");
                } else {
                    $user = new User();
                    $user->setEmail($request->request->get("email"));
                    $user->setPassword($userPasswordHasher->hashPassword($user, $request->request->get("password")));
                    $user->setCompanyName($request->request->get("nameCompany"));
                    $user->setPhoneNumber($request->request->get("phone"));
                    $user->setFullname($request->request->get("fullname"));
                    $user->setRoles(["ROLE_USER"]);
                    $user->setCreatedAt(new \DateTime());

                    $entityManager->persist($user);
                    $entityManager->flush();

                    $notificationService->sendRegister($user);
                    $notificationService->sendPromotionCode($user);


                    return $security->login($user, 'form_login', 'app');
                }
            }
        } else {
            foreach ($errors as $item) {
                $this->addFlash("error", $item);
            }
        }

        return $this->render('register/index.html.twig', [
            'controller_name' => 'RegisterController',
            'siteKey' => $_ENV['RECAPTCHA_SITE_KEY'] ?? ''
        ]);
    }
}
