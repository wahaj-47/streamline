<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Index' step.
 *
 * @Step(
 *   id = "index",
 *   label = @Translation("Index"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Index extends StepBase implements StepInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['key'] = [
            '#type' => 'textfield',
            '#title' => t('Key'),
            '#description' => t('Key to index'),
            '#default_value' => $this->configuration['key'] ?? "",
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input = NULL)
    {
        $key = $this->configuration['key'];

        $data = NULL;
        if (isset($input[$key])) {
            $data = $input[$key];
        }

        \Drupal::logger('streamline')->debug('Indexed data: @data', [
            '@data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $data;
    }
}
