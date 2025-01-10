<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'NotExactlyEqual' predicate.
 *
 * @Predicate(
 *   id = "not_exactly_equal",
 *   label = @Translation("Not Exactly Equal"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class NotExactlyEqual extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a !== $b;
    }
}
