<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'GreaterThan' predicate.
 *
 * @Predicate(
 *   id = "greater_than",
 *   label = @Translation("Greater than"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class GreaterThan extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a > $b;
    }
}
