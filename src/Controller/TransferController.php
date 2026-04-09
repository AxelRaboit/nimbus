<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transfer;
use App\Repository\TransferRepository;
use App\Service\TransferManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TransferController extends AbstractController
{
    #[Route('/t/{token}', name: 'transfer_show')]
    public function show(string $token, TransferRepository $transferRepository): Response
    {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer) {
            throw $this->createNotFoundException();
        }

        if (!$transfer->isReady() || $transfer->isExpired()) {
            return $this->render('transfer/unavailable.html.twig', [
                'transfer' => $transfer,
            ]);
        }

        return $this->render('transfer/show.html.twig', [
            'transfer' => $transfer,
        ]);
    }

    #[Route('/t/{token}/download', name: 'transfer_download')]
    public function download(string $token, TransferRepository $transferRepository): Response
    {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isReady() || $transfer->isExpired()) {
            throw $this->createNotFoundException();
        }

        // TODO: ZIP multiple files or serve single file directly
        return new Response('Download not yet implemented', Response::HTTP_NOT_IMPLEMENTED);
    }

    #[Route('/manage/{ownerToken}', name: 'transfer_manage')]
    public function manage(string $ownerToken, TransferRepository $transferRepository): Response
    {
        $transfer = $transferRepository->findByOwnerToken($ownerToken);

        if (!$transfer instanceof Transfer) {
            throw $this->createNotFoundException();
        }

        return $this->render('transfer/manage.html.twig', [
            'transfer' => $transfer,
        ]);
    }

    #[Route('/manage/{ownerToken}/delete', name: 'transfer_delete', methods: ['POST'])]
    public function delete(string $ownerToken, TransferRepository $transferRepository, TransferManager $transferManager): Response
    {
        $transfer = $transferRepository->findByOwnerToken($ownerToken);

        if (!$transfer instanceof Transfer) {
            throw $this->createNotFoundException();
        }

        $transferManager->delete($transfer);

        $this->addFlash('success', 'Votre transfert a bien été supprimé.');

        return $this->redirectToRoute('home');
    }
}
