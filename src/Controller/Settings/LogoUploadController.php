<?php


namespace App\Controller\Settings;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LogoUploadController extends AbstractController
{
    #[Route('/api/settings/logo', name: 'api_settings_logo', methods: ['POST'])]
    public function __invoke(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // CSRF (si tu utilises un formulaire classique) :
        $token = $request->request->get('_token');
        if ($token && !$this->isCsrfTokenValid('upload_logo', $token)) {
            return new JsonResponse(['error' => 'CSRF token invalide'], 419);
        }

        $file = $request->files->get('logo');  // Blob envoyé par le front
        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier'], 400);
        }

        // Validation basique
        $allowed = ['image/png','image/jpeg','image/webp'];
        if (!in_array($file->getClientMimeType(), $allowed, true)) {
            return new JsonResponse(['error' => 'Type de fichier non supporté'], 415);
        }
        if ($file->getSize() > 2 * 1024 * 1024) { // 2 Mo
            return new JsonResponse(['error' => 'Fichier trop volumineux'], 413);
        }

        // Dossier public (configurable)
        $publicDir = $this->getParameter('kernel.project_dir').'/public/uploads/';
        if (!is_dir($publicDir)) { @mkdir($publicDir, 0775, true); }

        // Nom de fichier unique
        $ext = $file->guessExtension() ?: 'png';
        $filename = bin2hex(random_bytes(8)).'.'.$ext;

        try {
            $file->move($publicDir, $filename);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Erreur lors de la copie'], 500);
        }

        // Sauvegarde BDD
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $publicUrl = '/uploads/'.$filename;

        $user->setStyleLogo($filename);
        $em->flush();

        return new JsonResponse(['url' => $publicUrl], 201);
    }
}
