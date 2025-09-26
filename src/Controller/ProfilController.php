<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\FileType;
use App\Service\FileService;
use App\VO\FileVO;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(Request $request, FileService $fileService, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $fileVO = new FileVO();

        $fileform = $this->createForm(FileType::class, $fileVO);
        $fileform->handleRequest($request);

        if ($fileform->isSubmitted() && $fileform->isValid()) {
            /** @var File $file */
            $file = $fileVO->file;

            if ($file instanceof File) {
                $nameFile             = $fileService->naming();
                $filenameandextension = $fileService->save($file, __DIR__ . "/../../public/uploads/", $nameFile);
                $user->setAvatar($filenameandextension);
            }

            $entityManager->flush();

            $this->addFlash("message", "Avatar mis à jour");
        }

        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'form' => $fileform->createView()
        ]);
    }
}
