<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Component\Plugin\PluginBase;

/**
 * Plugin implementation of the 'StrContains' predicate.
 *
 * @Predicate(
 *   id = "str_contains",
 *   label = @Translation("Str Contains"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class StrContains extends PluginBase implements PredicateInterface
{

    /**
     * {@inheritdoc}
     * Case insensitive
     */
    public function evaluate(mixed $a, mixed $b): bool
    {
        $a = strtolower($a);
        $b = strtolower($b);
        return str_contains($a, $b);
    }
}
