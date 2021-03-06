<?php declare(strict_types=1);

namespace ReactInspector\Tests\Collector\Merger;

use ReactInspector\CollectorInterface;
use Rx\Observable;
use function ApiClients\Tools\Rx\observableFromArray;

/**
 * @internal
 */
final class CollectorStubDifferent implements CollectorInterface
{
    public function collect(): Observable
    {
        return observableFromArray([]);
    }

    public function cancel(): void
    {
        // void
    }
}
