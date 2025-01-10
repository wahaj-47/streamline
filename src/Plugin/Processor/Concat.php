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
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        if (empty($value)) return "";

        if (count($value) > 1) {
            return join(" ", (array)$value);
        }
        return (string)$value;
    }
}
