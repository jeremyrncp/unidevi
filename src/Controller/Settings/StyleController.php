<?php

namespace App\Controller\Settings;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class StyleController extends AbstractController
{
    #[Route('/api/settings/styles', name: 'api_settings_styles', methods: ['POST'])]
    public function __invoke(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent());

        $styleType = $data->styleType;  // Blob envoyé par le front
        if (!$styleType) {
            return new JsonResponse(['error' => 'Aucun type de style'], 400);
        }

        $stylePolice = $data->stylePolice;  // Blob envoyé par le front
        if (!$stylePolice) {
            return new JsonResponse(['error' => 'Aucune police de style'], 400);
        }

        $styleColor = $data->styleColor;  // Blob envoyé par le front
        if (!$styleColor) {
            return new JsonResponse(['error' => 'Aucune police de style'], 400);
        }

        $user->setStyleType($styleType);
        $user->setStylePolice($stylePolice);
        $user->setStyleColor($styleColor);
        $em->flush();

        return new JsonResponse(['message' => "Style mis à jour"], 201);
    }

}
