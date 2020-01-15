<?php declare(strict_types=1);

namespace ReactInspector\Tests\Collector\Merger;

use ReactInspector\CollectorInterface;
use Rx\Observable;

/**
 * @internal
 */
final class CollectorStubSame implements CollectorInterface
{
    /** @var Observable */
    private $observable;

    public function __construct(Observable $observable)
    {
        $this->observable = $observable;
    }

    public function collect(): Observable
    {
        return $this->observable;
    }

    public function cancel(): void
    {
        // void
    }
}
