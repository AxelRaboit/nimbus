<?php

declare(strict_types=1);

namespace App\Storage;

use App\Enum\ContentTypeEnum;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class R2StorageAdapter implements StorageAdapterInterface
{
    private ?S3Client $client = null;

    public function __construct(
        private readonly ?string $endpoint,
        private readonly ?string $accessKeyId,
        private readonly ?string $secretAccessKey,
        private readonly string $bucket,
    ) {}

    public function store(string $sourcePath, string $storageKey): void
    {
        $this->getClient()->putObject([
            'Bucket' => $this->bucket,
            'Key' => $storageKey,
            'SourceFile' => $sourcePath,
        ]);

        unlink($sourcePath);
    }

    public function delete(string $storageKey): void
    {
        try {
            $this->getClient()->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $storageKey,
            ]);
        } catch (AwsException) {
            // Object may already be gone, ignore
        }
    }

    public function exists(string $storageKey): bool
    {
        try {
            $this->getClient()->headObject([
                'Bucket' => $this->bucket,
                'Key' => $storageKey,
            ]);

            return true;
        } catch (AwsException) {
            return false;
        }
    }

    public function createDownloadResponse(string $storageKey, string $originalName, ?string $mimeType, bool $inline): Response
    {
        $command = $this->getClient()->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $storageKey,
            'ResponseContentDisposition' => HeaderUtils::makeDisposition(
                $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
                $originalName,
            ),
            'ResponseContentType' => $mimeType ?: ContentTypeEnum::OctetStream->value,
        ]);

        $presignedRequest = $this->getClient()->createPresignedRequest($command, '+5 minutes');

        return new RedirectResponse((string) $presignedRequest->getUri(), Response::HTTP_FOUND);
    }

    public function getLocalPath(string $storageKey): string
    {
        $tmpPath = (string) tempnam(sys_get_temp_dir(), 'nimbus_r2_');

        $this->getClient()->getObject([
            'Bucket' => $this->bucket,
            'Key' => $storageKey,
            'SaveAs' => $tmpPath,
        ]);

        return $tmpPath;
    }

    private function getClient(): S3Client
    {
        if (!$this->client instanceof S3Client) {
            if (in_array($this->endpoint, [null, '', '0'], true) || in_array($this->accessKeyId, [null, '', '0'], true) || in_array($this->secretAccessKey, [null, '', '0'], true)) {
                throw new RuntimeException('R2 storage credentials are not configured. Set R2_ENDPOINT, R2_ACCESS_KEY_ID and R2_SECRET_ACCESS_KEY.');
            }

            $this->client = new S3Client([
                'version' => 'latest',
                'region' => 'auto',
                'endpoint' => $this->endpoint,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key' => $this->accessKeyId,
                    'secret' => $this->secretAccessKey,
                ],
            ]);
        }

        return $this->client;
    }
}
