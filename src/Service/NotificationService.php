<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Entity\AppointmentItem;
use App\Entity\User;
use App\Repository\TemplatedEmailRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationService
{
    public function __construct(private readonly TemplatedEmailRepository $templatedEmailRepository, private readonly Environment $twig, private readonly MailerInterface $mailer)
    {

    }

    public function sendRegister(User $user)
    {
        $template = $this->twig->render("email/register.html.twig", $this->getVariablesUser($user));

        $email = (new Email())
                ->to($user->getEmail())
                ->subject('Bienvenue sur Unidevi 🚀 — votre essai gratuit commence maintenant !')
                ->html($template)
                ->from($_ENV["SENDER_EMAIL"]);

        $this->mailer->send($email);
    }

    public function sendPromotionCode(User $user)
    {
        $template = $this->twig->render("email/promotion_code.html.twig", $this->getVariablesUser($user));

        $email = (new Email())->to($user->getEmail())
            ->subject('⏰ Offre limitée : 5 € offerts pour activer votre version Pro sur Unidevi !')
            ->html($template)->from($_ENV["SENDER_EMAIL"]);

        $this->mailer->send($email);
    }
    public function getVariablesUser(User $user): array
    {
        return [
            "fullname" => $user->getFullname()
        ];
    }
}
