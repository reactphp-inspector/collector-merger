<?php declare(strict_types=1);

namespace ReactInspector\Collector\Merger;

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
                            $metrics[$metric->config()->name()] = new Metric($metric->config(), [], []);
                        }

                        $measurements = [];

                        /** @var Measurement $measurement */
                        foreach ($metric->measurements() as $measurement) {
                            $measurements[] = new Measurement(
                                $measurement->value(),
                                ...\array_merge($metric->tags(), $measurement->tags())
                            );
                        }

                        $metrics[$metric->config()->name()] = new Metric(
                            $metric->config(),
                            [],
                            \array_merge($metrics[$metric->config()->name()]->measurements(), $measurements)
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
