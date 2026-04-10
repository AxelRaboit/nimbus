<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Recipient;
use App\Entity\Transfer;
use App\Entity\TransferFile;
use App\Entity\User;
use App\Enum\TransferStatusEnum;
use App\Enum\UserRoleEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // ── Users ────────────────────────────────────────────────────────────
        $dev = new User();
        $dev->setEmail('dev@nimbus.app');
        $dev->setName('Dev User');
        $dev->setRoles([UserRoleEnum::Dev->value]);
        $dev->setPassword($this->hasher->hashPassword($dev, 'password'));

        $manager->persist($dev);

        $user = new User();
        $user->setEmail('user@nimbus.app');
        $user->setName('Test User');
        $user->setPassword($this->hasher->hashPassword($user, 'password'));

        $manager->persist($user);

        $manager->flush();

        // ── Transfers ────────────────────────────────────────────────────────
        $this->createTransfer($manager, 'alice@example.com', 'Alice Martin', TransferStatusEnum::Ready, '+5 days', [
            ['rapport-annuel-2025.pdf', 2_450_000],
            ['annexes.xlsx', 890_000],
        ], ['bob@example.com', 'carol@example.com'], downloadedIndex: 0);

        $this->createTransfer($manager, 'bob@example.com', 'Bob Dupont', TransferStatusEnum::Ready, '+12 days', [
            ['photos-vacances.zip', 45_000_000],
        ], ['alice@example.com'], isPasswordProtected: true);

        $this->createTransfer($manager, 'carol@example.com', 'Carol Smith', TransferStatusEnum::Expired, '-1 day', [
            ['presentation.pptx', 5_100_000],
            ['notes.docx', 340_000],
        ], ['dev@nimbus.app']);

        $this->createTransfer($manager, 'david@example.com', null, TransferStatusEnum::Deleted, '-3 days', [
            ['archive.zip', 12_000_000],
        ], ['eve@example.com']);

        $this->createTransfer($manager, 'eve@example.com', 'Eve Leblanc', TransferStatusEnum::Ready, '+8 days', [
            ['contrat.pdf', 180_000],
            ['justificatifs.pdf', 220_000],
            ['signature.png', 95_000],
        ], ['legal@example.com', 'rh@example.com'], downloadedIndex: 0);

        $this->createTransfer($manager, null, null, TransferStatusEnum::Pending, '+7 days', [
            ['draft.txt', 12_000],
        ], ['pending-recipient@example.com']);

        $manager->flush();
    }

    private function createTransfer(
        ObjectManager $manager,
        ?string $senderEmail,
        ?string $senderName,
        TransferStatusEnum $status,
        string $expiresIn,
        array $files,
        array $recipientEmails,
        int $downloadedIndex = -1,
        bool $isPasswordProtected = false,
    ): void {
        $transfer = new Transfer();
        $transfer->setSenderEmail($senderEmail);
        $transfer->setSenderName($senderName);
        $transfer->setStatus($status);
        $transfer->setExpiresAt(new DateTimeImmutable($expiresIn));

        if ($isPasswordProtected) {
            $transfer->setPasswordHash(password_hash('secret', PASSWORD_BCRYPT));
        }

        foreach ($files as [$name, $size]) {
            $file = new TransferFile();
            $file->setOriginalName($name);
            $file->setFilename(bin2hex(random_bytes(8)).'_'.$name);
            $file->setFileSize($size);
            $file->setMimeType($this->guessMime($name));
            $transfer->addFile($file);
            $manager->persist($file);
        }

        foreach ($recipientEmails as $i => $email) {
            $recipient = new Recipient();
            $recipient->setEmail($email);
            if ($i === $downloadedIndex) {
                $recipient->markAsDownloaded();
            }

            $transfer->addRecipient($recipient);
            $manager->persist($recipient);
        }

        $manager->persist($transfer);
    }

    private function guessMime(string $name): string
    {
        return match (mb_strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'zip' => 'application/zip',
            'png' => 'image/png',
            'txt' => 'text/plain',
            default => 'application/octet-stream',
        };
    }
}
