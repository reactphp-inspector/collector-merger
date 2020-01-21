<?php declare(strict_types=1);

namespace ReactInspector\Collector\Merger;

use ReactInspector\Measurements;
use ReactInspector\Tags;
use function ApiClients\Tools\Rx\observableFromArray;
use function ApiClients\Tools\Rx\unwrapObservableFromPromise;
use function React\Promise\all;
use ReactInspector\CollectorInterface;
use ReactInspector\Measurement;
use ReactInspector\Metric;
use Rx\Observable;

final class CollectorMergerCollector implements CollectorInterface
{
    /** @var CollectorInterface[] */
    private $collectors = [];

    /**
     * @param CollectorInterface[] $collectors
     */
    public function __construct(CollectorInterface ...$collectors)
    {
        $previousClass = \get_class($collectors[0]);
        foreach ($collectors as $collector) {
            if (\get_class($collector) !== $previousClass) {
                throw CollectorsMustBeOfTheSameClassException::fromCollectors(...$collectors);
            }

            $previousClass = \get_class($collector);
        }
        $this->collectors = $collectors;
    }

    public function collect(): Observable
    {
        $promises = [];

        foreach ($this->collectors as $collector) {
            $promises[] = $collector->collect()->toArray()->toPromise();
        }

        return unwrapObservableFromPromise(
            all($promises)->then(function (array $metricCollections) {
                $metrics = [];

                foreach ($metricCollections as $metricCollection) {
                    /** @var Metric $metric */
                    foreach ($metricCollection as $metric) {
                        if (!\array_key_exists($metric->config()->name(), $metrics)) {
                            $metrics[$metric->config()->name()] = new Metric($metric->config(), new Tags(), new Measurements());
                        }

                        $measurements = [];

                        /** @var Measurement $measurement */
                        foreach ($metric->measurements()->get() as $measurement) {
                            $measurements[] = new Measurement(
                                $measurement->value(),
                                new Tags(...\array_values(\array_merge($metric->tags()->get(), $measurement->tags()->get())))
                            );
                        }

                        $metrics[$metric->config()->name()] = new Metric(
                            $metric->config(),
                            new Tags(),
                            new Measurements(...\array_merge($metrics[$metric->config()->name()]->measurements()->get(), $measurements))
                        );
                    }
                }

                return observableFromArray($metrics);
            })
        );
    }

    public function cancel(): void
    {
        // void
    }
}
