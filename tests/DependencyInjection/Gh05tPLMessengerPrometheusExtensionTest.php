<?php

declare(strict_types=1);

namespace Gh05tPL\MessengerPrometheusBundle\Tests\DependencyInjection;

use Gh05tPL\MessengerPrometheusBundle\DependencyInjection\Gh05tPLMessengerPrometheusExtension;
use Gh05tPL\MessengerPrometheusBundle\EventSubscriber\MessengerWorkerMetricsSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class Gh05tPLMessengerPrometheusExtensionTest extends TestCase
{
    public function testLoadsMessengerWorkerMetricsSubscriber(): void
    {
        $container = new ContainerBuilder();

        (new Gh05tPLMessengerPrometheusExtension())->load([], $container);

        self::assertTrue($container->hasDefinition(MessengerWorkerMetricsSubscriber::class));

        $definition = $container->getDefinition(MessengerWorkerMetricsSubscriber::class);

        self::assertTrue($definition->hasTag('kernel.event_subscriber'));
        self::assertTrue($definition->hasTag('prometheus_metrics_bundle.metrics_collector'));
    }
}

