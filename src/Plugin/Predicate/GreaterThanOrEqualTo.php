<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'GreaterThanOrEqualTo' predicate.
 *
 * @Predicate(
 *   id = "greater_than_or_equal_to",
 *   label = @Translation("Greater than or equal to"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class GreaterThanOrEqualTo extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a >= $b;
    }
}
