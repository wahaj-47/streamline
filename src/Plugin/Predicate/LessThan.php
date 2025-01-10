<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'LessThan' predicate.
 *
 * @Predicate(
 *   id = "less_than",
 *   label = @Translation("Less than"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class LessThan extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a < $b;
    }
}
