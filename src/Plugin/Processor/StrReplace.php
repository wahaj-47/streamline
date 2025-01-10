<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'StrReplace' processor.
 *
 * @Processor(
 *   id = "str_replace",
 *   label = @Translation("Str Replace"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class StrReplace extends PluginBase implements ProcessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form['search'] = [
            '#type' => "textfield",
            '#title' => t('Search'),
            '#default_value' => $this->configuration['search'] ?? ''
        ];

        $form['replace'] = [
            '#type' => "textfield",
            '#title' => t('Replace'),
            '#default_value' => $this->configuration['replace'] ?? ''
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     * 
     * @param $options['search']
     * The value being searched for, otherwise known as the needle. An array may be used to designate multiple needles
     * 
     * @param $options['replace']
     * The replacement value that replaces found search values. An array may be used to designate multiple replacements.
     * 
     */
    public function process($value)
    {
        $search = $this->configuration['search'];
        $replace = $this->configuration['replace'];

        return str_replace($search, $replace, $value);
    }
}
