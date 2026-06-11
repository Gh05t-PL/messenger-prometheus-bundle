<?php

declare(strict_types=1);

namespace Gh05tPL\MessengerPrometheusBundle\Tests\EventSubscriber;

use Gh05tPL\MessengerPrometheusBundle\EventSubscriber\MessengerWorkerMetricsSubscriber;
use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\MetricFamilySamples;
use Prometheus\Sample;
use Prometheus\Storage\InMemory;
use RuntimeException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;

final class MessengerWorkerMetricsSubscriberTest extends TestCase
{
    public function testSubscribesToMessengerWorkerEvents(): void
    {
        self::assertSame(
            [
                WorkerMessageReceivedEvent::class => 'onMessageReceived',
                WorkerMessageHandledEvent::class => 'onMessageHandled',
                WorkerMessageFailedEvent::class => 'onMessageFailed',
                WorkerMessageRetriedEvent::class => 'onMessageRetried',
            ],
            MessengerWorkerMetricsSubscriber::getSubscribedEvents()
        );
    }

    public function testDoesNothingBeforeCollectorRegistryIsInitialized(): void
    {
        $subscriber = new MessengerWorkerMetricsSubscriber();

        $subscriber->onMessageReceived(new WorkerMessageReceivedEvent(
            new Envelope(new ExampleMessage()),
            'async',
        ));

        self::expectNotToPerformAssertions();
    }

    public function testIncrementsWorkerEventCounters(): void
    {
        $registry = $this->createRegistry();
        $subscriber = new MessengerWorkerMetricsSubscriber();
        $subscriber->init('test', $registry);
        $envelope = new Envelope(new ExampleMessage());

        $subscriber->onMessageReceived(new WorkerMessageReceivedEvent($envelope, 'async'));
        $subscriber->onMessageHandled(new WorkerMessageHandledEvent($envelope, 'async'));
        $subscriber->onMessageRetried(new WorkerMessageRetriedEvent($envelope, 'async'));

        $samples = $this->samplesByLabels(
            $this->metricFamily($registry, 'test_messenger_worker_messages_total')
        );

        self::assertSame('1', $samples[$this->labelKey([
            'received',
            ExampleMessage::class,
            'async',
        ])]->getValue());
        self::assertSame('1', $samples[$this->labelKey([
            'handled',
            ExampleMessage::class,
            'async',
        ])]->getValue());
        self::assertSame('1', $samples[$this->labelKey([
            'retried',
            ExampleMessage::class,
            'async',
        ])]->getValue());
    }

    public function testIncrementsFailureCounterWithExceptionAndRetryLabels(): void
    {
        $registry = $this->createRegistry();
        $subscriber = new MessengerWorkerMetricsSubscriber();
        $subscriber->init('test', $registry);
        $event = new WorkerMessageFailedEvent(
            new Envelope(new ExampleMessage()),
            'failed_transport',
            new RuntimeException('Handler failed.'),
        );
        $event->setForRetry();

        $subscriber->onMessageFailed($event);

        $workerSamples = $this->samplesByLabels(
            $this->metricFamily($registry, 'test_messenger_worker_messages_total')
        );
        $failureSamples = $this->samplesByLabels(
            $this->metricFamily($registry, 'test_messenger_worker_failures_total')
        );

        self::assertSame('1', $workerSamples[$this->labelKey([
            'failed',
            ExampleMessage::class,
            'failed_transport',
        ])]->getValue());
        self::assertSame('1', $failureSamples[$this->labelKey([
            ExampleMessage::class,
            'failed_transport',
            RuntimeException::class,
            'yes',
        ])]->getValue());
    }

    private function createRegistry(): CollectorRegistry
    {
        return new CollectorRegistry(new InMemory(), false);
    }

    private function metricFamily(CollectorRegistry $registry, string $name): MetricFamilySamples
    {
        foreach ($registry->getMetricFamilySamples() as $metricFamilySamples) {
            if ($metricFamilySamples->getName() === $name) {
                return $metricFamilySamples;
            }
        }

        self::fail(sprintf('Metric family "%s" was not collected.', $name));
    }

    /**
     * @return array<string, Sample>
     */
    private function samplesByLabels(MetricFamilySamples $metricFamilySamples): array
    {
        $samples = [];

        foreach ($metricFamilySamples->getSamples() as $sample) {
            $samples[$this->labelKey($sample->getLabelValues())] = $sample;
        }

        return $samples;
    }

    /**
     * @param list<string> $labelValues
     */
    private function labelKey(array $labelValues): string
    {
        return implode("\n", $labelValues);
    }
}

final class ExampleMessage
{
}

