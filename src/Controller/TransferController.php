<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Transfer;
use App\Repository\TransferRepository;
use App\Service\TransferManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use ZipArchive;

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
    public function download(
        string $token,
        TransferRepository $transferRepository,
        #[Autowire('%transfer_storage_path%')]
        string $transferStoragePath,
    ): Response {
        $transfer = $transferRepository->findByToken($token);

        if (!$transfer instanceof Transfer || !$transfer->isReady() || $transfer->isExpired()) {
            throw $this->createNotFoundException();
        }

        $files = $transfer->getFiles()->toArray();

        if (1 === count($files)) {
            $file = $files[0];
            $path = sprintf('%s/%s/%s', $transferStoragePath, $transfer->getToken(), $file->getFilename());

            return new BinaryFileResponse(
                $path,
                Response::HTTP_OK,
                [
                    'Content-Disposition' => HeaderUtils::makeDisposition(
                        HeaderUtils::DISPOSITION_ATTACHMENT,
                        $file->getOriginalName(),
                    ),
                    'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
                ],
            );
        }

        // Multiple files → stream as ZIP
        $zipName = sprintf('nimbus-%s.zip', mb_strtolower($transfer->getReference()));
        $tmpZip = tempnam(sys_get_temp_dir(), 'nimbus_zip_');

        $zip = new ZipArchive();
        $zip->open($tmpZip, ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $path = sprintf('%s/%s/%s', $transferStoragePath, $transfer->getToken(), $file->getFilename());
            if (file_exists($path)) {
                $zip->addFile($path, $file->getOriginalName());
            }
        }

        $zip->close();

        $response = new BinaryFileResponse(
            $tmpZip,
            Response::HTTP_OK,
            [
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $zipName,
                ),
                'Content-Type' => 'application/zip',
            ],
        );

        $response->deleteFileAfterSend(true);

        return $response;
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
