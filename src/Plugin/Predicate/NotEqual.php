<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'Equal' predicate.
 *
 * @Predicate(
 *   id = "not_equal",
 *   label = @Translation("Not Equal"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class NotEqual extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a != $b;
    }
}
