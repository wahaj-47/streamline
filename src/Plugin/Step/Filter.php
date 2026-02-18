<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Filter' step.
 *
 * @Step(
 *   id = "filter",
 *   label = @Translation("Filter"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Filter extends StepBase implements StepInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['extracted_elements'] = [
            '#type' => 'textarea',
            '#title' => t('Extracted Elements'),
            '#description' => t('Comma separated list of elements to extract.'),
            '#default_value' => $this->configuration['extracted_elements'] ?? '',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input = NULL)
    {
        if (empty($input)) {
            \Drupal::logger('streamline')->error('Filtering fields error: $input is empty');
            return NULL;
        }

        $extracted_elements = array_map('trim', explode(',', $this->configuration['extracted_elements']));
        $extracted_elements = array_filter($extracted_elements, fn($value) => !empty($value));

        // Converting wildcards to regular expressions
        $extracted_elements = array_map(function ($element) {
            // Escape dots and replace * with regex equivalent.
            $regex = preg_quote($element, '/');

            // Replace '*' with '.*' for regex.
            $regex = str_replace('\*', '.*', $regex);

            return "/^$regex$/";
        }, $extracted_elements);

        $data = array_map(function ($record) use ($extracted_elements) {
            $filtered = [];

            foreach ($record as $key => $value) {
                foreach ($extracted_elements as $regex) {
                    if (preg_match($regex, $key)) {
                        $filtered[$key] = $value;
                        break;
                    }
                }
            }

            return $filtered;
        }, $input);

        return $data;
    }
}
