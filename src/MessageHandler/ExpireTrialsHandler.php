<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Enum\PlanEnum;
use App\Message\ExpireTrialsMessage;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ExpireTrialsHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ExpireTrialsMessage $message): void
    {
        $expired = $this->userRepository->createQueryBuilder('u')
            ->where('u.plan = :plan')
            ->andWhere('u.proUntil IS NOT NULL')
            ->andWhere('u.proUntil < :now')
            ->setParameter('plan', PlanEnum::Pro)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();

        $count = 0;
        foreach ($expired as $user) {
            $user->setPlan(PlanEnum::Free);
            $user->setProUntil(null);
            ++$count;
        }

        if ($count > 0) {
            $this->entityManager->flush();
            $this->logger->info('Expired {count} Pro trial(s).', ['count' => $count]);
        }
    }
}
