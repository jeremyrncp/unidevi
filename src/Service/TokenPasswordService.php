<?php

namespace App\Service;

use App\Entity\TokenPassword;
use App\Entity\User;
use App\Repository\TokenPasswordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class TokenPasswordService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory
    ) {
    }

    public function sendPasswordReset(string $email): bool
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        /** @var User $user */
        $user = $userRepository->findOneBy(['email' => $email]);

        if ($user === null) {
            throw new NotFoundHttpException("user not found");
        }

        $token = $this->generateToken();

        $tokenPassword = new TokenPassword();
        $tokenPassword->setToken($token)
                      ->setOwner($user);

        $this->entityManager->persist($tokenPassword);
        $this->entityManager->flush();

        $email = (new TemplatedEmail())
            ->from($_ENV["SENDER_EMAIL"])
            ->to($email)
            ->subject('Definition du mot de passe')
            ->htmlTemplate('email/reset_password.html.twig')
            ->context([
                'user' => $user,
                'connectLink' => $this->getConnectLink($token)
            ])
        ;

        $this->mailer->send($email);

        return true;
    }

    public function resetPassword(string $token, string $plainPassword)
    {
        /** @var TokenPasswordRepository $tokenPasswordRepository */
        $tokenPasswordRepository = $this->entityManager->getRepository(TokenPassword::class);

        /** @var TokenPassword $tokenPassword */
        $tokenPassword = $tokenPasswordRepository->findOneBy(['token' => $token]);

        if ($tokenPassword?->getUsedAt() === null) {
            $user = $tokenPassword->getOwner();
            $user->setPassword($this->passwordHasherFactory->getPasswordHasher($user)->hash($plainPassword));

            $tokenPassword->setUsedAt(new \DateTime());

            $this->entityManager->flush();

            return true;
        }

        return false;
    }
    private function getConnectLink(string $token): string
    {
        return $_ENV['URL_FRONT'] . "reset_password/" . $token;
    }

    private function generateToken(): string
    {
        return sha1(time().rand());
    }
}
