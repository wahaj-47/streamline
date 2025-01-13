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
        if (empty($value)) return "";

        $format = $this->configuration['format'];
        if (empty($format)) return $value;

        preg_match_all('/\{([^}]*)\}/', $format, $matches);

        $values = array_map(
            function ($item) use ($value) {
                return isset($value[$item]) ? $value[$item] : '';
            },
            $matches[1]
        );

        $format = preg_replace('/\{([^}]*)\}/', '%s', $format);

        return vsprintf($format, $values);
    }
}
