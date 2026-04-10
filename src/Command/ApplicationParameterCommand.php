<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\ApplicationParameter;
use App\Enum\ApplicationParameter\NimbusApplicationParameterEnum;
use App\Repository\ApplicationParameterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:application-parameter',
    description: 'Synchronise les paramètres applicatifs (crée les manquants, supprime les obsolètes).',
    aliases: ['app:ap'],
)]
class ApplicationParameterCommand extends Command
{
    public function __construct(
        private readonly ApplicationParameterRepository $repository,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les changements sans les appliquer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Mode dry-run — aucun changement ne sera enregistré.');
        }

        $enumCases = NimbusApplicationParameterEnum::cases();
        $enumKeys = array_map(fn ($c): string => $c->getKey(), $enumCases);

        $existing = [];
        foreach ($this->repository->findAll() as $param) {
            $existing[$param->getKey()] = $param;
        }

        $created = 0;
        $deleted = 0;

        // ── Créer les paramètres manquants ────────────────────────────────────
        foreach ($enumCases as $case) {
            if (isset($existing[$case->getKey()])) {
                // Met à jour description si elle a changé
                $param = $existing[$case->getKey()];
                if ($param->getDescription() !== $case->getDescription()) {
                    $io->writeln(sprintf('  <comment>~</comment> %s (description mise à jour)', $case->getKey()));
                    if (!$dryRun) {
                        $param->setDescription($case->getDescription());
                    }
                }

                continue;
            }

            $io->writeln(sprintf('  <info>+</info> %s (défaut : %s)', $case->getKey(), $case->getDefaultValue()));
            ++$created;

            if (!$dryRun) {
                $param = new ApplicationParameter($case->getKey(), $case->getDefaultValue(), $case->getDescription());
                $this->em->persist($param);
            }
        }

        // ── Supprimer les paramètres obsolètes (hors compteurs stats.*) ───────
        foreach ($existing as $key => $param) {
            if (str_starts_with($key, 'stats.')) {
                continue; // compteurs runtime, ne pas toucher
            }

            if (!in_array($key, $enumKeys, true)) {
                $io->writeln(sprintf('  <fg=red>-</fg=red> %s (obsolète)', $key));
                ++$deleted;
                if (!$dryRun) {
                    $this->em->remove($param);
                }
            }
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success(sprintf('%d créé(s), %d supprimé(s).', $created, $deleted));

        return Command::SUCCESS;
    }
}
