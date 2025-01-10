<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Base Fetch' step.
 */
class FetchBase extends StepBase implements StepInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['endpoint'] = [
            '#type' => 'textfield',
            '#title' => t('Endpoint'),
            '#description' => t('Endpoint to fetch the data from.'),
            '#default_value' => $this->configuration['endpoint'] ?? '',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     * Must be overriden in child classes
     */
    public function execute($input = NULL) {}
}
