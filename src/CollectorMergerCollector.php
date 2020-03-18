<?php declare(strict_types=1);

namespace ReactInspector\Collector\Merger;

use ReactInspector\CollectorInterface;
use ReactInspector\Measurement;
use ReactInspector\Measurements;
use ReactInspector\Metric;
use ReactInspector\Tags;
use Rx\Observable;
use function ApiClients\Tools\Rx\observableFromArray;
use function ApiClients\Tools\Rx\unwrapObservableFromPromise;
use function array_key_exists;
use function array_merge;
use function array_values;
use function assert;
use function current;
use function get_class;
use function React\Promise\all;

final class CollectorMergerCollector implements CollectorInterface
{
    /** @var array<int, CollectorInterface> */
    private array $collectors = [];

    /**
     * @param array<int, CollectorInterface> $collectors
     */
    public function __construct(CollectorInterface ...$collectors)
    {
        $previousClass = get_class(current($collectors));
        foreach ($collectors as $collector) {
            if (get_class($collector) !== $previousClass) {
                throw CollectorsMustBeOfTheSameClassException::fromCollectors(...$collectors);
            }

            $previousClass = get_class($collector);
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
            all($promises)->then(static function (array $metricCollections): Observable {
                $metrics = [];

                foreach ($metricCollections as $metricCollection) {
                    foreach ($metricCollection as $metric) {
                        assert($metric instanceof Metric);
                        if (! array_key_exists($metric->config()->name(), $metrics)) {
                            $metrics[$metric->config()->name()] = Metric::create($metric->config(), new Tags(), new Measurements());
                        }

                        $measurements = [];

                        foreach ($metric->measurements()->get() as $measurement) {
                            $measurements[] = new Measurement(
                                $measurement->value(),
                                new Tags(...array_values(array_merge($metric->tags()->get(), $measurement->tags()->get())))
                            );
                        }

                        $metrics[$metric->config()->name()] = Metric::create(
                            $metric->config(),
                            new Tags(),
                            new Measurements(...array_merge($metrics[$metric->config()->name()]->measurements()->get(), $measurements))
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
