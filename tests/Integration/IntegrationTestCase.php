<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

abstract class IntegrationTestCase extends WebTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $kernel = static::bootKernel();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $fixtures = $container->get(AppFixtures::class);

        $executor = new ORMExecutor($em, new ORMPurger($em));
        $executor->execute([$fixtures]);

        $application = new Application($kernel);
        $application->setAutoExit(false);
        $application->run(
            new ArrayInput(['command' => 'nimbus:application-parameter']),
            new NullOutput(),
        );

        static::ensureKernelShutdown();
    }
}
