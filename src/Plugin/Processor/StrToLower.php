<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'StrToLower' processor.
 *
 * @Processor(
 *   id = "str_to_lower",
 *   label = @Translation("Str To Lower"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class StrToLower extends PluginBase implements ProcessorInterface
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
     * @return string — the lowercased string.
     * 
     */
    public function process($value)
    {
        if (is_array($value)) {
            return array_map(
                [$this, 'toLowercase'],
                $value
            );
        }

        return $this->toLowercase($value);
    }

    private function toLowercase($value)
    {
        if (is_string($value)) {
            return strtolower($value);
        }

        return $value;
    }
}
