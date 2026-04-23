<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\DataFixtures\AppFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

abstract class IntegrationTestCase extends WebTestCase
{
    /**
     * Decode the props passed to a Vue component mounted via `{{ vue_component('Name', {...}) }}`.
     * Returns the deserialized props array, or [] if the component was not found.
     *
     * @return array<string, mixed>
     */
    protected static function getVueProps(KernelBrowser $client, string $componentName): array
    {
        $selector = sprintf('[data-symfony--ux-vue--vue-component-value="%s"]', $componentName);
        $node = $client->getCrawler()->filter($selector);
        if (0 === $node->count()) {
            return [];
        }

        $raw = $node->attr('data-symfony--ux-vue--vue-props-value');
        if (null === $raw || '' === $raw) {
            return [];
        }

        return json_decode($raw, true) ?? [];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $kernel = static::bootKernel();
        $container = static::getContainer();

        $entityManager = $container->get(EntityManagerInterface::class);
        $fixtures = $container->get(AppFixtures::class);

        $executor = new ORMExecutor($entityManager, new ORMPurger($entityManager));
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
