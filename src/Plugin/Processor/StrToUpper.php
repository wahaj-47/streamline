<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'StrToUpper' processor.
 *
 * @Processor(
 *   id = "str_to_upper",
 *   label = @Translation("Str To Upper"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class StrToUpper extends PluginBase implements ProcessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        return $form;
    }

    /**
     * {@inheritdoc}
     * 
     * @param string $string — The input string.
     * 
     * @return string — the uppercased string.
     * 
     */
    public function process($value)
    {
        if (is_string($value)) {
            return strtoupper($value);
        }

        return $value;
    }
}
