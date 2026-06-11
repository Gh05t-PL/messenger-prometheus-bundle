<?php

declare(strict_types=1);

use Gh05tPL\MessengerPrometheusBundle\EventSubscriber\MessengerWorkerMetricsSubscriber;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->defaults()
        ->autowire()
        ->private();

    $services
        ->set(MessengerWorkerMetricsSubscriber::class)
        ->tag('kernel.event_subscriber')
        ->tag('prometheus_metrics_bundle.metrics_collector');
};