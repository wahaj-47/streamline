<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'LessThanOrEqualTo' predicate.
 *
 * @Predicate(
 *   id = "less_than_or_equal_to",
 *   label = @Translation("Less than or equal to"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class LessThanOrEqualTo extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a <= $b;
    }
}
