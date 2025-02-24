<?php

namespace Drupal\streamline\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Processor annotation object.
 *
 * @Annotation
 */
class Processor extends Plugin
{
    /**
     * The plugin ID.
     *
     * @var string
     */
    public $id;

    /**
     * The human-readable name of the step type.
     *
     * @var \Drupal\Core\Annotation\Translation
     * @ingroup plugin_translatable
     */
    public $label;

    /**
     * A short description of the step type.
     *
     * @var \Drupal\Core\Annotation\Translation
     * @ingroup plugin_translatable
     */
    public $description;
}
