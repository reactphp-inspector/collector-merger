<?php declare(strict_types=1);

namespace ReactInspector\Tests\Collector\Merger;

use ReactInspector\Collector\Merger\CollectorMergerCollector;
use ReactInspector\Collector\Merger\CollectorsMustBeOfTheSameClassException;
use ReactInspector\Config;
use ReactInspector\Measurement;
use ReactInspector\Measurements;
use ReactInspector\Metric;
use ReactInspector\Tag;
use ReactInspector\Tags;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use function ApiClients\Tools\Rx\observableFromArray;
use function array_values;
use function assert;

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
            new CollectorStubDifferent(),
        );
    }

    /**
     * @test
     */
    public function mergesMetrics(): void
    {
        $config  = new Config(
            'name',
            'counter',
            'Halp'
        );
        $metricA = Metric::create(
            $config,
            new Tags(
                new Tag('a', 'b'),
            ),
            new Measurements(
                new Measurement(123.456, new Tags(new Tag('zz', 'xx'))),
            )
        );
        $metricC = Metric::create(
            $config,
            new Tags(
                new Tag('c', 'd'),
            ),
            new Measurements(
                new Measurement(789.101112, new Tags(new Tag('vv', 'ww'))),
            )
        );
        $metric  = $this->await((new CollectorMergerCollector(
            new CollectorStubSame(observableFromArray([$metricA])),
            new CollectorStubSame(observableFromArray([$metricC]))
        ))->collect()->take(1)->toArray()->toPromise())[0];
        assert($metric instanceof Metric);

        self::assertCount(0, $metric->tags()->get());
        self::assertCount(2, $metric->measurements()->get());

        foreach ($metric->measurements()->get() as $measurement) {
            self::assertTrue(
                (
                    $measurement->value() === 123.456 &&
                    (string) new Tags(...array_values($measurement->tags()->get())) === 'a=b,zz=xx'
                ) ||
                (
                    $measurement->value() === 789.101112 &&
                    (string) new Tags(...array_values($measurement->tags()->get())) === 'c=d,vv=ww'
                )
            );
        }
    }
}
