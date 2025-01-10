<?php

namespace Drupal\streamline\Plugin\Predicate;

/**
 * Defines the interface for processor plugins.
 */
interface PredicateInterface
{
    /**
     * Process the input value.
     *
     * @param mixed $a
     * @param mixed $b
     *
     * @return bool
     */
    public function evaluate(mixed $a, mixed $b): bool;
}
