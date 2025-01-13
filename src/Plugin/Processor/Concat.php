<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Concat' processor.
 *
 * @Processor(
 *   id = "concat",
 *   label = @Translation("Concat"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Concat extends PluginBase implements ProcessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form['separator'] = [
            '#type' => "textfield",
            '#title' => t('Separator'),
            '#default_value' => $this->configuration['separator'] ?? ' ' // Defaults to a space
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        if (empty($value)) return "";

        if (is_array($value)) {
            $separator = $this->configuration['separator'] ?? ' ';
            return join($separator, $value);
        }

        return (string)$value;
    }
}
