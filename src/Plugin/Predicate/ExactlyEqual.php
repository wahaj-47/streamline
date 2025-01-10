<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'ExactlyEqual' predicate.
 *
 * @Predicate(
 *   id = "exact_equal",
 *   label = @Translation("Exactly Equal"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class ExactlyEqual extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a === $b;
    }
}
