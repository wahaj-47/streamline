<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for processor plugins.
 */
interface ProcessorInterface
{

    /*
    * Defines fields required by the plugin
    */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state);

    /**
     * Process the input value.
     *
     * @param mixed $value
     * The value to be processed.
     *
     * @return mixed
     * The processed value.
     */
    public function process($value);
}
