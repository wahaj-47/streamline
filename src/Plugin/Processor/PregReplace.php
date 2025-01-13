<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'PregReplace' processor.
 *
 * @Processor(
 *   id = "preg_replace",
 *   label = @Translation("Preg Replace"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class PregReplace extends PluginBase implements ProcessorInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form['search'] = [
            '#type' => "textfield",
            '#title' => t('Pattern'),
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
     * Pattern to search for. It can be string or array with strings
     * 
     */
    public function process($value)
    {
        $search = $this->configuration['search'];
        $replace = $this->configuration['replace'];

        if (empty($search) || empty($replace)) return $value;

        if (is_array($value)) {
            return array_map(
                function ($item) use ($search, $replace) {
                    preg_replace($search, $replace, $item);
                },
                $value
            );
        }

        return preg_replace($search, $replace, $value);
    }
}
