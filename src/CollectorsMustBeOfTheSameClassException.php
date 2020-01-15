<?php declare(strict_types=1);

namespace ReactInspector\Collector\Merger;

use ReactInspector\CollectorInterface;

final class CollectorsMustBeOfTheSameClassException extends \InvalidArgumentException
{
    private const MESSAGE = 'Collectors all must be of the same class, but received these instead: %s';

    public static function fromCollectors(CollectorInterface ...$collectors): CollectorsMustBeOfTheSameClassException
    {
        $classes = \array_map('get_class', $collectors);

        return new self(\sprintf(self::MESSAGE, \implode(', ', $classes)));
    }
}
