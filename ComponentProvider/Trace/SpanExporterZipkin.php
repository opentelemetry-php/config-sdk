<?php

declare(strict_types=1);

namespace OpenTelemetry\Config\SDK\ComponentProvider\Trace;

use Nevay\SPI\ServiceProviderDependency\PackageDependency;
use OpenTelemetry\API\Common\Time\ClockInterface;
use OpenTelemetry\API\Configuration\Config\ComponentProvider;
use OpenTelemetry\API\Configuration\Config\ComponentProviderRegistry;
use OpenTelemetry\API\Configuration\Context;
use OpenTelemetry\Config\SDK\Configuration\Validation;
use OpenTelemetry\Contrib\Zipkin;
use OpenTelemetry\SDK\Registry;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * @implements ComponentProvider<SpanExporterInterface>
 */
#[PackageDependency('open-telemetry/exporter-zipkin', '^1.0')]
final class SpanExporterZipkin implements ComponentProvider
{
    /**
     * @param array{
     *     endpoint: string,
     *     timeout: int<0, max>,
     * } $properties
     */
    #[\Override]
    public function createPlugin(array $properties, Context $context): SpanExporterInterface
    {
        return new Zipkin\Exporter(Registry::transportFactory('http')->create(
            endpoint: $properties['endpoint'],
            contentType: 'application/json',
            timeout: $properties['timeout'] / ClockInterface::MILLIS_PER_SECOND,
        ));
    }

    #[\Override]
    public function getConfig(ComponentProviderRegistry $registry, NodeBuilder $builder): ArrayNodeDefinition
    {
        $node = $builder->arrayNode('zipkin');
        $node
            ->children()
                ->scalarNode('endpoint')->isRequired()->validate()->always(Validation::ensureString())->end()->end()
                ->integerNode('timeout')->min(0)->defaultValue(10000)->end()
            ->end()
        ;

        return $node;
    }
}
