# Messenger Prometheus Bundle

Symfony bundle exposing Messenger worker metrics for Prometheus.

It integrates with `artprima/prometheus-metrics-bundle` and increments counters when
Symfony Messenger workers receive, handle, fail, or retry messages.

## Installation

```bash
composer require gh05tpl/messenger-prometheus-bundle
```

If your application does not use Symfony Flex auto-registration, enable both
bundles manually:

```php
// config/bundles.php

return [
    Artprima\PrometheusMetricsBundle\ArtprimaPrometheusMetricsBundle::class => ['all' => true],
    Gh05tPL\MessengerPrometheusBundle\Gh05tPLMessengerPrometheusBundle::class => ['all' => true],
];
```

## Configure Prometheus Metrics Bundle

For Messenger worker metrics, use shared storage so the worker process and the
HTTP process exposing metrics can see the same counters. Redis is the safest
default.

```yaml
# config/packages/artprima_prometheus_metrics.yaml
artprima_prometheus_metrics:
  namespace: app
  storage:
    type: redis
    host: '%env(default:redis:REDIS_HOST)%'
    port: '%env(int:default:6379:REDIS_PORT)%'
    prefix: app_prometheus
```

For a quick local smoke test only, you can use in-memory storage:

```yaml
# config/packages/artprima_prometheus_metrics.yaml
artprima_prometheus_metrics:
  namespace: app
  storage:
    type: in_memory
```

In-memory storage is not recommended for real Messenger worker metrics because
workers and web requests usually run in different PHP processes.

## Expose Metrics Endpoint

Import the route provided by `artprima/prometheus-metrics-bundle`:

```yaml
# config/routes/prometheus.yaml
prometheus_metrics:
  resource: '@ArtprimaPrometheusMetricsBundle/Resources/config/routing.yaml'
```

The endpoint is available at:

```text
/metrics/prometheus
```

Point Prometheus at that endpoint, for example:

```yaml
scrape_configs:
  - job_name: symfony
    metrics_path: /metrics/prometheus
    static_configs:
      - targets:
          - app.example.com
```

## Run Messenger Workers

No extra code is required. Once the bundle is enabled, it subscribes to Symfony
Messenger worker events automatically.

```bash
php bin/console messenger:consume async -vv
```

The bundle records these counters:

```text
app_messenger_worker_messages_total{event,message_class,receiver_name}
app_messenger_worker_failures_total{message_class,receiver_name,exception_class,will_retry}
```

The `event` label can be one of:

```text
received
handled
failed
retried
```

The metric prefix comes from `artprima_prometheus_metrics.namespace`. If you set
`namespace: my_app`, the counters will be named:

```text
my_app_messenger_worker_messages_total
my_app_messenger_worker_failures_total
```

## Development

This repository includes a Docker-based development environment.

```bash
make build
make install
make test
make validate
```
