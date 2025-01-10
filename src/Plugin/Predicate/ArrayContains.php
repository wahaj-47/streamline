<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'ArrayContains' predicate.
 *
 * @Predicate(
 *   id = "array_contains",
 *   label = @Translation("Array Contains"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class ArrayContains extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     * Case insensitive
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return in_array($b, $a);
    }
}
