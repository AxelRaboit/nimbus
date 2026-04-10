<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\AllowedExtensionEnum;
use App\Enum\ExpiryOptionEnum;
use App\Repository\ApplicationParameterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ApplicationParameterRepository $params): Response
    {
        return $this->render('home/index.html.twig', [
            'maxSizeMb' => (int) $params->get('max_transfer_size_mb'),
            'maxFiles' => (int) $params->get('max_files_per_transfer'),
            'maxRecipients' => (int) $params->get('max_recipients_per_transfer'),
            'maxExpiryDays' => (int) $params->get('max_expiry_days'),
            'expiryOptions' => ExpiryOptionEnum::validOptions((int) $params->get('max_expiry_days')),
            'extensionGroups' => AllowedExtensionEnum::groupedValues(),
        ]);
    }
}
