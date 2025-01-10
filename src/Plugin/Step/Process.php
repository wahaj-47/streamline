<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'OAI-PMH fetch' step.
 *
 * @Step(
 *   id = "process",
 *   label = @Translation("Process extracted fields"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Process extends StepBase implements StepInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        // Custom props set on $form array
        $parents = $form['#parents'];
        $delta = $form['#delta'];

        $form['fields'] = [
            '#type' => 'fieldset',
            '#title' => t('Fields'),
            '#collapsible' => FALSE,
            '#description' => t('Add fields for processing.'),
            '#attributes' => [
                'id' => 'processor-step-' . $delta
            ],
        ];

        $form['fields']['actions']['add_field_button'] = [
            '#type' => 'submit',
            '#value' => t('Add Field'),
            '#name' => 'add_field_' . $delta,
            '#submit' => [[get_class($this), 'addFieldSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildStep'],
                'wrapper' => 'processor-step-' . $delta,
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        $form['fields']['actions']['remove_field_button'] = [
            '#type' => 'submit',
            '#value' => t('Remove Field'),
            '#name' => 'remove_field_' . $delta,
            '#submit' => [[get_class($this), 'removeFieldSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildStep'],
                'wrapper' => 'processor-step-' . $delta,
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        if (!$form_state->has('step-' . $delta . '-field-count') && isset($this->configuration['fields'])) {
            $form_state->set('step-' . $delta . '-field-count', count($this->configuration['fields']));
        }

        $field_count = $form_state->get('step-' . $delta . '-field-count') ?? 0;

        for ($i = 0; $i < $field_count; $i++) {

            $form['fields'][$i] = [
                '#type' => 'details',
                '#title' => 'Field-' . ($i + 1),
                '#open' => FALSE,
            ];

            $form['fields'][$i]['identifier'] = [
                '#type' => 'textfield',
                '#title' => 'Identifier',
                '#default_value' => $this->configuration['fields'][$i]['identifier'] ?? ''
            ];

            $form['fields'][$i]['actions']['add_processor_button'] = [
                '#type' => 'submit',
                '#value' => t('Add Processor'),
                '#name' => 'add_field_' . $delta . '_processor_' . $i,
                '#submit' => [[get_class($this), 'addProcessorSubmit']],
                '#ajax' => [
                    'callback' => [get_class($this), 'ajaxRebuildField'],
                    'wrapper' => 'processor-step-' . $delta . '-field-' . $i,
                    'delta' => $delta,
                    'index' => $i
                ],
                '#limit_validation_errors' => []
            ];

            $form['fields'][$i]['actions']['remove_processor_button'] = [
                '#type' => 'submit',
                '#value' => t('Remove Processor'),
                '#name' => 'remove_field_' . $delta . '_processor_' . $i,
                '#submit' => [[get_class($this), 'removeProcessorSubmit']],
                '#ajax' => [
                    'callback' => [get_class($this), 'ajaxRebuildField'],
                    'wrapper' => 'processor-step-' . $delta . '-field-' . $i,
                    'delta' => $delta,
                    'index' => $i
                ],
                '#limit_validation_errors' => []
            ];

            $form['fields'][$i]['processors'] = [
                '#type' => 'fieldset',
                '#title' => t('Processors'),
                '#collapsible' => FALSE,
                '#attributes' => [
                    'id' => 'processor-step-' . $delta . '-field-' . $i
                ],
                '#description' => t('Add processors.'),
            ];

            if (!$form_state->has('step-' . $delta . '-field-' . $i . '-processor-count') && isset($this->configuration['fields'][$i]['processors'])) {
                $form_state->set('step-' . $delta . '-field-' . $i . '-processor-count', count($this->configuration['fields'][$i]['processors']));
            }

            $processor_count = $form_state->get('step-' . $delta . '-field-' . $i . '-processor-count') ?? 0;

            for ($j = 0; $j < $processor_count; $j++) {
                $form['fields'][$i]['processors'][$j] = [
                    '#type' => 'details',
                    '#title' => 'Processor-' . ($j + 1),
                    '#open' => TRUE,
                    '#attributes' => [
                        'id' => 'processor-step-' . $delta . '-field-' . $i . '-processor-' . $j . '-config'
                    ]
                ];

                $form['fields'][$i]['processors'][$j]['processor_type'] = [
                    '#type' => 'select',
                    '#options' => $this->getAvailableProcessors(),
                    '#empty_option' => t('- Select -'),
                    '#default_value' => $this->configuration['fields'][$i]['processors'][$j]['processor_type'] ?? '',
                    '#ajax' => [
                        'callback' => [get_class($this), 'ajaxRebuildProcessorConfig'],
                        'wrapper' => 'processor-step-' . $delta . '-field-' . $i . '-processor-' . $j . '-config'
                    ],
                    '#limit_validation_errors' => [
                        array_merge(
                            $parents,
                            ['fields', $i, 'processors', $j, 'processor_type']
                        )
                    ]
                ];

                $processer_type_key = array_merge(
                    $parents,
                    ['fields', $i, 'processors', $j, 'processor_type']
                );

                if (
                    !$form_state->hasValue($processer_type_key) &&
                    isset($this->configuration['fields'][$i]['processors'][$j]['processor_type'])
                ) {
                    $form_state->setValue(
                        $processer_type_key,
                        $this->configuration['fields'][$i]['processors'][$j]['processor_type']
                    );
                }

                $plugin_id = $form_state->getValue($processer_type_key);

                $form['fields'][$i]['processors'][$j]['config'] = [
                    '#type' => 'container',
                ];

                $plugin_manager = \Drupal::service('plugin.manager.processor');
                if ($plugin_id) {
                    $plugin_instance = $plugin_manager->createInstance(
                        $plugin_id,
                        $this->configuration['fields'][$i]['processors'][$j]['config'] ?? []
                    );

                    // Passing in custom props via $form array
                    $form['fields'][$i]['processors'][$j]['config']['#delta'] = $delta;
                    $form['fields'][$i]['processors'][$j]['config']['#index'] = $i;
                    $form['fields'][$i]['processors'][$j]['config']['#jindex'] = $j;

                    $form['fields'][$i]['processors'][$j]['config'] = $plugin_instance->buildConfigurationForm($form['fields'][$i]['processors'][$j]['config'], $form_state);

                    $form_state->set('plugin_instance-field-' . $i . '-processor-' . $j, $plugin_instance);
                }
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(FormStateInterface $form_state, array $parent)
    {
        parent::save($form_state, $parent);
        $form_state->addCleanValueKey(array_merge($parent, ['fields', 'actions']));
        $form_state->addCleanValueKey(array_merge($parent, ['fields', 'actions', 'remove_field_button']));
        $form_state->addCleanValueKey(array_merge($parent, ['fields', 'actions', 'add_field_button']));

        // The last key of parent will be the $delta (step number)
        $delta = $parent[count($parent) - 1];
        $field_count = $form_state->get('step-' . $delta . '-field-count') ?? 0;
        for ($i = 0; $i < $field_count; $i++) {
            $form_state->addCleanValueKey(array_merge($parent, ['fields', $i, 'actions']));
        }
    }

    /**
     * AJAX callback to rebuild the step.
     */
    public static function ajaxRebuildStep(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $parents = $triggerElement['#parents'];

        // Removing [actions, {trigger}-field-button] from parents
        $parents = array_slice($parents, 0, -2);

        $fields = &$form;
        foreach ($parents as $key) {
            $fields = &$fields[$key];
        }
        return $fields;
    }

    /**
     * Submit handler to add a field.
     */
    public static function addFieldSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $field_count = $form_state->get('step-' . $delta . '-field-count');
        $form_state->set('step-' . $delta . '-field-count', $field_count + 1);

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove a field.
     */
    public static function removeFieldSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $field_count = $form_state->get('step-' . $delta . '-field-count');
        if ($field_count > 0) {
            $form_state->set('step-' . $delta . '-field-count', $field_count - 1);
        }

        $form_state->setRebuild(TRUE);
    }

    /**
     * AJAX callback to rebuild the field.
     */
    public static function ajaxRebuildField(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $parents = $triggerElement['#parents'];

        // Removing [actions, {trigger}-field-button] from parents
        $parents = array_slice($parents, 0, -2);
        $parents = array_merge($parents, ['processors']);

        $processors = &$form;
        foreach ($parents as $key) {
            $processors = &$processors[$key];
        }
        return $processors;
    }

    /**
     * Submit handler to add a processor.
     */
    public static function addProcessorSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];
        $i = $triggerElement['#ajax']['index'];

        $processor_count = $form_state->get('step-' . $delta . '-field-' . $i . '-processor-count');
        $form_state->set('step-' . $delta . '-field-' . $i . '-processor-count', $processor_count + 1);

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove a processor.
     */
    public static function removeProcessorSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];
        $i = $triggerElement['#ajax']['index'];

        $processor_count = $form_state->get('step-' . $delta . '-field-' . $i . '-processor-count');
        if ($processor_count > 0) {
            $form_state->set('step-' . $delta . '-field-' . $i . '-processor-count', $processor_count - 1);
        }

        $form_state->setRebuild(TRUE);
    }

    /*
    * AJAX callback to rebuild processor config
    */
    public static function ajaxRebuildProcessorConfig(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $parents = $triggerElement['#parents'];

        // Removing [processor_type] from parents
        $config_parents = array_slice($parents, 0, -1);

        $config_form = &$form;
        foreach ($config_parents as $key) {
            $config_form = &$config_form[$key];
        }

        return $config_form;
    }

    /**
     * Getter for all available processors
     */
    public function getAvailableProcessors()
    {
        try {
            $plugin_manager = \Drupal::service('plugin.manager.processor');
            $plugins = $plugin_manager->getDefinitions();

            $options = [];
            foreach ($plugins as $plugin_id => $plugin_definition) {
                $options[$plugin_id] = $plugin_definition['label'];
            }

            return $options;
        } catch (\Throwable $e) {
            \Drupal::logger('streamline')->error('Error: @message', ['@message' => $e->getMessage()]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input = NULL)
    {
        if (empty($input)) {
            \Drupal::logger('streamline')->error('Processing fields error: $input is empty');
            return NULL;
        }

        $fields = $this->configuration['fields'];

        $data = array_map(function ($record) use ($fields) {
            foreach ($fields as $field) {
                if (!isset($field['identifier'])) continue;

                $identifier = $field['identifier'];
                $processors = $field['processors'];
                foreach ($processors as $processor) {
                    $plugin_id = $processor['processor_type'];
                    $config = $processor['config'] ?? [];

                    $plugin_manager = \Drupal::service('plugin.manager.processor');
                    $plugin_instance = $plugin_manager->createInstance(
                        $plugin_id,
                        $config
                    );

                    /** 
                     * @var \Drupal\streamline\Plugin\Processor\ProcessorInterface $plugin_instance
                     */
                    $record[$identifier] = $plugin_instance->process($record[$identifier]);
                }
            }
            return $record;
        }, $input);


        \Drupal::logger('streamline')->debug('Processed data: @data', [
            '@data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $data;
    }
}
