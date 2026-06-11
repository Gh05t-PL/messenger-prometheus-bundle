<?php

declare(strict_types=1);

namespace Gh05tPL\MessengerPrometheusBundle\EventSubscriber;

use Artprima\PrometheusMetricsBundle\Metrics\MetricsCollectorInterface;
use Prometheus\CollectorRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;

final class MessengerWorkerMetricsSubscriber implements EventSubscriberInterface, MetricsCollectorInterface
{
    private string $namespace = 'app';

    private ?CollectorRegistry $collectionRegistry = null;

    public function init(string $namespace, CollectorRegistry $collectionRegistry): void
    {
        $this->namespace = $namespace;
        $this->collectionRegistry = $collectionRegistry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageReceivedEvent::class => 'onMessageReceived',
            WorkerMessageHandledEvent::class => 'onMessageHandled',
            WorkerMessageFailedEvent::class => 'onMessageFailed',
            WorkerMessageRetriedEvent::class => 'onMessageRetried',
        ];
    }

    public function onMessageReceived(WorkerMessageReceivedEvent $event): void
    {
        $this->incrementWorkerEvent('received', $event->getEnvelope(), $event->getReceiverName());
    }

    public function onMessageHandled(WorkerMessageHandledEvent $event): void
    {
        $this->incrementWorkerEvent('handled', $event->getEnvelope(), $event->getReceiverName());
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $this->incrementWorkerEvent('failed', $event->getEnvelope(), $event->getReceiverName());

        $this->incrementFailure(
            $event->getEnvelope(),
            $event->getReceiverName(),
            $event->getThrowable()::class,
            $event->willRetry()
        );
    }

    public function onMessageRetried(WorkerMessageRetriedEvent $event): void
    {
        $this->incrementWorkerEvent('retried', $event->getEnvelope(), $event->getReceiverName());
    }

    private function incrementWorkerEvent(string $eventName, Envelope $envelope, string $receiverName): void
    {
        if (!$this->collectionRegistry instanceof CollectorRegistry) {
            return;
        }

        $message = $envelope->getMessage();

        $counter = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'messenger_worker_messages_total',
            'Total Messenger worker message events.',
            ['event', 'message_class', 'receiver_name']
        );

        $counter->inc([
            $eventName,
            $message::class,
            $receiverName,
        ]);
    }

    private function incrementFailure(
        Envelope $envelope,
        string $receiverName,
        string $exceptionClass,
        bool $willRetry,
    ): void {
        if (!$this->collectionRegistry instanceof CollectorRegistry) {
            return;
        }

        $message = $envelope->getMessage();

        $counter = $this->collectionRegistry->getOrRegisterCounter(
            $this->namespace,
            'messenger_worker_failures_total',
            'Total failed Messenger worker messages.',
            ['message_class', 'receiver_name', 'exception_class', 'will_retry']
        );

        $counter->inc([
            $message::class,
            $receiverName,
            $exceptionClass,
            $willRetry ? 'yes' : 'no',
        ]);
    }
}