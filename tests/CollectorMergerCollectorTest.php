<?php declare(strict_types=1);

namespace ReactInspector\Tests\Collector\Merger;

use function ApiClients\Tools\Rx\observableFromArray;
use ReactInspector\Collector\Merger\CollectorMergerCollector;
use ReactInspector\Collector\Merger\CollectorsMustBeOfTheSameClassException;
use ReactInspector\Config;
use ReactInspector\Measurement;
use ReactInspector\Metric;
use ReactInspector\Tag;
use ReactInspector\Tags;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

/**
 * @internal
 */
final class CollectorMergerCollectorTest extends AsyncTestCase
{
    /**
     * @test
     */
    public function rejectsDifferentCollectors(): void
    {
        self::expectException(CollectorsMustBeOfTheSameClassException::class);

        new CollectorMergerCollector(
            new CollectorStubSame(observableFromArray([])),
            new CollectorStubSame(observableFromArray([])),
            new CollectorStubSame(observableFromArray([])),
            new CollectorStubSame(observableFromArray([])),
            new CollectorStubSame(observableFromArray([])),
            new CollectorStubSame(observableFromArray([])),
            new CollectorStubSame(observableFromArray([])),
            new CollectorStubDifferent()
        );
    }

    /**
     * @test
     */
    public function mergesMetrics(): void
    {
        $config = new Config(
            'name',
            'counter',
            'Halp'
        );
        $metricA = new Metric(
            $config,
            [
                new Tag('a', 'b'),
            ],
            [
                new Measurement(123.456, new Tag('zz', 'xx')),
            ]
        );
        $metricC = new Metric(
            $config,
            [
                new Tag('c', 'd'),
            ],
            [
                new Measurement(789.101112, new Tag('vv', 'ww')),
            ]
        );
        /** @var Metric $metric */
        $metric = $this->await((new CollectorMergerCollector(
            new CollectorStubSame(observableFromArray([$metricA])),
            new CollectorStubSame(observableFromArray([$metricC]))
        ))->collect()->take(1)->toArray()->toPromise())[0];

        self::assertCount(0, $metric->tags());
        self::assertCount(2, $metric->measurements());

        /** @var Measurement $measurement */
        foreach ($metric->measurements() as $measurement) {
            self::assertTrue(
                (
                    $measurement->value() === 123.456 &&
                    (string)new Tags(...$measurement->tags()) === 'a=b,zz=xx'
                ) ||
                (
                    $measurement->value() === 789.101112 &&
                    (string)new Tags(...$measurement->tags()) === 'c=d,vv=ww'
                )
            );
        }
    }
}
