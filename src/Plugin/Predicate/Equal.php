<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'Equal' predicate.
 *
 * @Predicate(
 *   id = "equal",
 *   label = @Translation("Equal"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Equal extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        return $a == $b;
    }
}
