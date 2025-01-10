<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Base Step' plugin.
 */
class StepBase extends PluginBase implements StepInterface
{

    /**
     * {@inheritdoc}
     * Must be overriden in child classes
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form['type'] = [
            '#type' => 'hidden',
            '#title' => 'ID',
            '#disabled' => TRUE,
            '#default_value' => $this->configuration['type']
        ];

        $form['label'] = [
            '#type' => 'hidden',
            '#title' => 'Label',
            '#disabled' => TRUE,
            '#default_value' => $this->configuration['label']
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(FormStateInterface $form_state, array $parent)
    {
        $form_state->addCleanValueKey(array_merge($parent, ['operations']));
    }

    /**
     * {@inheritdoc}
     * Must be overriden in child classes
     */
    public function execute($input = NULL)
    {
        \Drupal::logger('streamline')->error('Step Base: execute');
    }
}
