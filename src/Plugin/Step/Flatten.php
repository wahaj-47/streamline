<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Flatten' step.
 *
 * @Step(
 *   id = "flatten",
 *   label = @Translation("Flatten"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Flatten extends StepBase implements StepInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input = NULL)
    {
        $data = array_map(function ($record) {
            return $this->flattenArray($record);
        }, $input);

        \Drupal::logger('streamline')->debug('Flattened data: @data', [
            '@data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $data;
    }

    private function flattenArray(array $array): array
    {
        $flattened = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $subFlattened = $this->flattenArray($value);
                foreach ($subFlattened as $subKey => $subValue) {
                    $flattened[$key . '.' . $subKey] = $subValue;
                }
            } else {
                $flattened[$key] = $value;
            }
        }
        return $flattened;
    }
}
