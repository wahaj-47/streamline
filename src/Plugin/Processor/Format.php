<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Format' processor.
 *
 * @Processor(
 *   id = "format",
 *   label = @Translation("Format"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Format extends PluginBase implements ProcessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form['format'] = [
            '#type' => "textfield",
            '#title' => t('Format'),
            '#description' => t('Assumes input will be an associative array. Use curly braces for params. Example: {title} - {url}'),
            '#default_value' => $this->configuration['format'] ?? ''
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        if (empty($value) || empty($this->configuration['format'])) return $value;

        if (is_array($value)) {
            return array_map([$this, 'format'], $value);
        }

        return $this->format($value);
    }

    private function format($value)
    {
        preg_match_all('/\{([^}]*)\}/', $this->configuration['format'], $out);
        $values = array_map(
            function ($item) use ($value) {
                return isset($value[$item]) ? $value[$item] : '';
            },
            $out[1]
        );

        $format = preg_replace('/\{([^}]*)\}/', '%s', $this->configuration['format']);
        return vsprintf($format, $values);
    }
}
