<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\HttpMethodEnum;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use TusPhp\Cache\FileStore as TusFileStore;
use TusPhp\Tus\Server as TusServer;

class TusController
{
    public function __construct(
        private readonly string $tusUploadPath,
        private readonly string $tusCachePath,
    ) {}

    #[Route('/tus', name: 'tus_create', methods: [HttpMethodEnum::Options->value, HttpMethodEnum::Post->value])]
    #[Route('/tus/{uploadKey}', name: 'tus_handle', methods: [HttpMethodEnum::Options->value, HttpMethodEnum::Head->value, HttpMethodEnum::Patch->value, HttpMethodEnum::Delete->value])]
    public function handle(): Response
    {
        $cache = new TusFileStore($this->tusCachePath);

        $server = new TusServer($cache);
        $server->setUploadDir($this->tusUploadPath);
        $server->setApiPath('/tus');

        return $server->serve();
    }
}
