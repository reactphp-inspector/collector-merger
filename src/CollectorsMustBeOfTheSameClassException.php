<?php declare(strict_types=1);

namespace ReactInspector\Collector\Merger;

use InvalidArgumentException;
use ReactInspector\CollectorInterface;
use function array_map;
use function implode;
use function Safe\sprintf;

final class CollectorsMustBeOfTheSameClassException extends InvalidArgumentException
{
    private const MESSAGE = 'Collectors all must be of the same class, but received these instead: %s';

    public static function fromCollectors(CollectorInterface ...$collectors): CollectorsMustBeOfTheSameClassException
    {
        $classes = array_map('get_class', $collectors);

        return new self(sprintf(self::MESSAGE, implode(', ', $classes)));
    }
}
