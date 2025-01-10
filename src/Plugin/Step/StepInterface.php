<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for step plugins.
 */
interface StepInterface
{
    /*
    * Defines fields required by the plugin
    */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state);

    /**
     * Get the values of fields defined by the plugin 
     */
    public function save(FormStateInterface $form_state, array $parent);

    /**
     * Executes the plugin logic.
     */
    public function execute($input = NULL);
}
