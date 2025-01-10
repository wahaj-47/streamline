<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Plugin implementation of the 'Tamper' step.
 *
 * @Step(
 *   id = "tamper",
 *   label = @Translation("Tamper extracted fields"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Tamper extends StepBase implements StepInterface
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

        $form['description'] = [
            '#markup' => "<small>Replaces the identifier value with the selected field if conditions are met</small>"
        ];

        $form['fields'] = [
            '#type' => 'fieldset',
            '#title' => t('Fields'),
            '#collapsible' => FALSE,
            '#description' => t('Add fields for tampering.'),
            '#attributes' => [
                'id' => 'tamper-step-' . $delta
            ],
        ];

        $form['fields']['actions']['add_field_button'] = [
            '#type' => 'submit',
            '#value' => t('Add Field'),
            '#name' => 'add_field_' . $delta,
            '#submit' => [[get_class($this), 'addFieldSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildStep'],
                'wrapper' => 'tamper-step-' . $delta,
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
                'wrapper' => 'tamper-step-' . $delta,
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
                '#prefix' => '<div id="step-' . $delta . '-field-' . $i . '-wrapper">',
                '#suffix' => '</div>',
            ];

            $form['fields'][$i]['replace'] = [
                '#type' => 'fieldset',
                '#title' => 'Replace',
                '#open' => TRUE,
            ];

            $form['fields'][$i]['replace']['identifier'] = [
                '#type' => 'textfield',
                '#title' => 'Identifier',
                '#default_value' => $this->configuration['fields'][$i]['replace']['identifier'] ?? ''
            ];

            $form['fields'][$i]['replacement'] = [
                '#type' => 'fieldset',
                '#title' => 'With',
                '#open' => TRUE,
            ];

            $form['fields'][$i]['replacement']['content_type'] = [
                '#type' => 'select',
                '#title' => t('Content Type'),
                '#options' => $this->getContentTypeOptions(),
                '#empty_option' => t('- Select -'),
                '#ajax' => [
                    'callback' => [get_class($this), 'updateFieldsDropdown'],
                    'wrapper' => 'step-' . $delta . '-field-' . $i . '-wrapper',
                    'event' => 'change',
                ],
                '#default_value' => $this->configuration['fields'][$i]['replacement']['content_type'] ?? '',
            ];

            $content_type_key = array_merge($parents, ['fields', $i, 'replacement', 'content_type']);

            if (!$form_state->hasValue($content_type_key) && isset($this->configuration['fields'][$i]['replacement']['content_type'])) {
                $form_state->setValue($content_type_key, $this->configuration['fields'][$i]['replacement']['content_type']);
            }

            $selected_content_type = $form_state->getValue($content_type_key);

            $form['fields'][$i]['replacement']['entity_fields'] = [
                '#type' => 'select',
                '#title' => t('Field'),
                '#options' => $this->getFieldsOptions($selected_content_type),
                '#empty_option' => t('- Select a field -'),
                '#default_value' => $this->configuration['fields'][$i]['replacement']['entity_fields'] ?? '',
            ];

            $form['fields'][$i]['tamper_conditions'] = [
                '#type' => 'fieldset',
                '#title' => 'If',
                '#collapsible' => FALSE,
                '#description' => t('Add conditions'),
                '#attributes' => [
                    'id' => 'tamper-step-' . $delta . '-field-' . $i . '-conditions'
                ],
            ];

            $form['fields'][$i]['tamper_conditions']['actions']['add_condition_group_button'] = [
                '#type' => 'submit',
                '#value' => t('Add Condition Group'),
                '#name' => 'add_field_' . $delta . '_condition_group_' . $i,
                '#submit' => [[get_class($this), 'addConditionGroupSubmit']],
                '#ajax' => [
                    'callback' => [get_class($this), 'ajaxRebuildCondition'],
                    'wrapper' => 'tamper-step-' . $delta . '-field-' . $i . '-conditions',
                    'delta' => $delta,
                    'index' => $i
                ],
                '#limit_validation_errors' => []
            ];

            $form['fields'][$i]['tamper_conditions']['actions']['remove_condition_group_button'] = [
                '#type' => 'submit',
                '#value' => t('Remove Condition Group'),
                '#name' => 'remove_field_' . $delta . '_condition_group_' . $i,
                '#submit' => [[get_class($this), 'removeConditionGroupSubmit']],
                '#ajax' => [
                    'callback' => [get_class($this), 'ajaxRebuildCondition'],
                    'wrapper' => 'tamper-step-' . $delta . '-field-' . $i . '-conditions',
                    'delta' => $delta,
                    'index' => $i
                ],
                '#limit_validation_errors' => []
            ];

            if (!$form_state->has('step-' . $delta . '-field-' . $i . '-condition-group-count') && isset($this->configuration['fields'][$i]['tamper_conditions'])) {
                $form_state->set('step-' . $delta . '-field-' . $i . '-condition-group-count', count($this->configuration['fields'][$i]['tamper_conditions']));
            }

            $condition_group_count = $form_state->get('step-' . $delta . '-field-' . $i . '-condition-group-count') ?? 0;

            for ($j = 0; $j < $condition_group_count; $j++) {
                $form['fields'][$i]['tamper_conditions'][$j]['group'] = [
                    '#type' => 'details',
                    '#title' => 'Condition-Group-' . ($j + 1),
                    '#open' => TRUE,
                    '#attributes' => [
                        'id' => 'tamper-step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-conditions'
                    ],
                ];

                $form['fields'][$i]['tamper_conditions'][$j]['group']['actions']['add_condition_button'] = [
                    '#type' => 'submit',
                    '#value' => t('Add Condition'),
                    '#name' => 'add_field_' . $delta . '_condition_group_' . $i . '_condition_' . $j,
                    '#submit' => [[get_class($this), 'addConditionSubmit']],
                    '#ajax' => [
                        'callback' => [get_class($this), 'ajaxRebuildConditionGroup'],
                        'wrapper' => 'tamper-step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-conditions',
                        'delta' => $delta,
                        'index' => $i,
                        'jindex' => $j,
                    ],
                    '#limit_validation_errors' => []
                ];

                $form['fields'][$i]['tamper_conditions'][$j]['group']['actions']['remove_condition_button'] = [
                    '#type' => 'submit',
                    '#value' => t('Remove Condition'),
                    '#name' => 'remove_field_' . $delta . '_condition_group_' . $i . '_condition_' . $j,
                    '#submit' => [[get_class($this), 'removeConditionSubmit']],
                    '#ajax' => [
                        'callback' => [get_class($this), 'ajaxRebuildConditionGroup'],
                        'wrapper' => 'tamper-step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-conditions',
                        'delta' => $delta,
                        'index' => $i,
                        'jindex' => $j,
                    ],
                    '#limit_validation_errors' => []
                ];

                if (!$form_state->has('step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-condition-count') && isset($this->configuration['fields'][$i]['tamper_conditions'][$j]['group'])) {
                    $form_state->set('step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-condition-count', count($this->configuration['fields'][$i]['tamper_conditions'][$j]['group']));
                }

                $condition_count = $form_state->get('step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-condition-count') ?? 0;

                for ($k = 0; $k < $condition_count; $k++) {
                    $form['fields'][$i]['tamper_conditions'][$j]['group'][$k]['condition'] = [
                        '#type' => 'details',
                        '#title' => 'Condition-' . ($k + 1),
                        '#open' => TRUE,
                    ];

                    $form['fields'][$i]['tamper_conditions'][$j]['group'][$k]['condition']['haystack'] = [
                        '#type' => 'textfield',
                        '#title' => 'Identifier',
                        '#default_value' => $this->configuration['fields'][$i]['tamper_conditions'][$j]['group'][$k]['condition']['haystack'] ?? ''
                    ];

                    $form['fields'][$i]['tamper_conditions'][$j]['group'][$k]['condition']['predicate'] = [
                        '#type' => 'select',
                        '#title' => t('Operator'),
                        '#options' => $this->getAvailableOperators(),
                        '#empty_option' => t('- Select -'),
                        '#default_value' => $this->configuration['fields'][$i]['tamper_conditions'][$j]['group'][$k]['condition']['predicate'] ?? ''
                    ];

                    $form['fields'][$i]['tamper_conditions'][$j]['group'][$k]['condition']['entity_fields'] = [
                        '#type' => 'select',
                        '#title' => t('Field'),
                        '#options' => $this->getFieldsOptions($selected_content_type),
                        '#empty_option' => t('- Select a field -'),
                        '#default_value' => $this->configuration['fields'][$i]['tamper_conditions'][$j]['group'][$k]['condition']['entity_fields'] ?? '',
                        '#description' => t('Try reselecting the content type in "With" section if the dropdown is empty')
                    ];

                    if ($k < $condition_count - 1) {
                        $form['fields'][$i]['tamper_conditions'][$j]['group'][$k]['operator'] = [
                            '#markup' => '<b>AND</b>'
                        ];
                    }
                }

                if ($j < $condition_group_count - 1) {
                    $form['fields'][$i]['tamper_conditions'][$j]['operator'] = [
                        '#markup' => '<b>OR</b>'
                    ];
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

        // The last key of parent will be the $delta (step number)
        $delta = $parent[count($parent) - 1];
        $field_count = $form_state->get('step-' . $delta . '-field-count') ?? 0;

        for ($i = 0; $i < $field_count; $i++) {
            $form_state->addCleanValueKey(array_merge($parent, ['fields', $i, 'actions']));
            $form_state->addCleanValueKey(array_merge($parent, ['fields', $i, 'tamper_conditions', 'actions']));

            $condition_group_count = $form_state->get('step-' . $delta . '-field-' . $i . '-condition-group-count') ?? 0;

            for ($j = 0; $j < $condition_group_count; $j++) {
                $form_state->addCleanValueKey(array_merge($parent, ['fields', $i, 'tamper_conditions', $j, 'group', 'actions']));
            }
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
     * AJAX callback to rebuild the condition groups.
     */
    public static function ajaxRebuildCondition(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $parents = $triggerElement['#parents'];

        // Removing [actions, {trigger}-field-button] from parents
        $parents = array_slice($parents, 0, -2);

        $conditions = &$form;
        foreach ($parents as $key) {
            $conditions = &$conditions[$key];
        }
        return $conditions;
    }

    /**
     * Submit handler to add a condition group.
     */
    public static function addConditionGroupSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];
        $i = $triggerElement['#ajax']['index'];

        $condition_group_count = $form_state->get('step-' . $delta . '-field-' . $i . '-condition-group-count');
        $form_state->set('step-' . $delta . '-field-' . $i . '-condition-group-count', $condition_group_count + 1);

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove a condition group.
     */
    public static function removeConditionGroupSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];
        $i = $triggerElement['#ajax']['index'];

        $condition_group_count = $form_state->get('step-' . $delta . '-field-' . $i . '-condition-group-count');
        if ($condition_group_count > 0) {
            $form_state->set('step-' . $delta . '-field-' . $i . '-condition-group-count', $condition_group_count - 1);
        }

        $form_state->setRebuild(TRUE);
    }

    /**
     * AJAX callback to rebuild a condition group.
     */
    public static function ajaxRebuildConditionGroup(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $parents = $triggerElement['#parents'];

        // Removing [actions, {trigger}-field-button] from parents
        $parents = array_slice($parents, 0, -2);

        $conditionGroup = &$form;
        foreach ($parents as $key) {
            $conditionGroup = &$conditionGroup[$key];
        }
        return $conditionGroup;
    }

    /**
     * Submit handler to add a condition group.
     */
    public static function addConditionSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];
        $i = $triggerElement['#ajax']['index'];
        $j = $triggerElement['#ajax']['jindex'];

        $condition_count = $form_state->get('step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-condition-count');
        $form_state->set('step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-condition-count', $condition_count + 1);

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove a condition group.
     */
    public static function removeConditionSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];
        $i = $triggerElement['#ajax']['index'];
        $j = $triggerElement['#ajax']['jindex'];

        $condition_group_count = $form_state->get('step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-condition-count');
        if ($condition_group_count > 0) {
            $form_state->set('step-' . $delta . '-field-' . $i . '-condition-group-' . $j . '-condition-count', $condition_group_count - 1);
        }

        $form_state->setRebuild(TRUE);
    }

    /**
     * Helper function to get content types as options.
     */
    protected function getContentTypeOptions()
    {
        $options = [];
        $types = NodeType::loadMultiple(); // Load all content types
        foreach ($types as $type) {
            $options[$type->id()] = $type->label(); // Key => Value
        }
        return $options;
    }

    /**
     * Helper function to get fields of the selected content type.
     */
    protected function getFieldsOptions($selected_content_type)
    {
        $options = [];

        if (!empty($selected_content_type)) {
            $field_definitions = \Drupal::service('entity_field.manager')
                ->getFieldDefinitions('node', $selected_content_type);

            foreach ($field_definitions as $field_name => $field_definition) {
                $options[$field_name] = $field_definition->getLabel();
            }
        }

        return $options;
    }

    /**
     * Helper function to get predicate plugins.
     */
    protected function getAvailableOperators()
    {
        $plugin_manager = \Drupal::service('plugin.manager.predicate');
        $plugins = $plugin_manager->getDefinitions();

        $options = [];
        foreach ($plugins as $plugin_id => $plugin_definition) {
            $options[$plugin_id] = $plugin_definition['label'];
        }

        return $options;
    }

    /**
     * AJAX callback to update the fields dropdown.
     */
    public static function updateFieldsDropdown(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $parents = $triggerElement['#parents'];

        $parents = array_slice($parents, 0, -2);

        $entity_fields = &$form;
        foreach ($parents as $key) {
            $entity_fields = &$entity_fields[$key];
        }

        return $entity_fields;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input = NULL)
    {
        if (empty($input)) {
            \Drupal::logger('streamline')->error('Tampering fields error: $input is empty');
            return NULL;
        }

        $fields = $this->configuration['fields'];
        $data = array_map(function ($record) use ($fields) {
            foreach ($fields as $field) {
                if (
                    !isset($field['replace']['identifier']) // The identifier to replace is undefined
                    || // OR
                    !isset($field['replacement']['entity_fields']) // The entity field to replace with is undefined
                )
                    continue;

                $with = NULL;

                $canTamper = false;
                foreach ($field['tamper_conditions'] as $group) {
                    if (!isset($group['group'])) continue;


                    $allConditionsMet = true;
                    foreach ($group['group'] as $condition) {

                        if (!isset($condition['condition'])) continue;

                        $haystack = $record[$condition['condition']['haystack']];
                        $plugin_id = $condition['condition']['predicate'];
                        $node_type = $field['replacement']['content_type'];
                        $node_field = $condition['condition']['entity_fields'];

                        // Load plugin
                        $plugin_manager = \Drupal::service('plugin.manager.predicate');
                        $plugin_instance = $plugin_manager->createInstance(
                            $plugin_id,
                        );

                        // Load all nodes of node type
                        $nodes = \Drupal::entityTypeManager()
                            ->getStorage('node')
                            ->loadByProperties(['type' => $node_type]);

                        $currentConditionMet = false;

                        // Foreach node: pass the haystack and $node[$node_field] to the predicate plugin
                        foreach ($nodes as $node) {

                            /**
                             * @var \Drupal\Core\Entity\ContentEntityBase $node
                             */
                            $needle = $node->get($node_field)->getString();

                            /**
                             * @var \Drupal\streamline\Plugin\Predicate\PredicateInterface $plugin_instance
                             */
                            $currentConditionMet = $plugin_instance->evaluate($haystack, $needle);

                            if ($currentConditionMet) {
                                switch ($field['replacement']['entity_fields']) {
                                    case 'nid':
                                        $with = $node->id();
                                        break;

                                    default:
                                        $with = $node->get($field['replacement']['entity_fields']);
                                        break;
                                }

                                break;
                            }
                        }

                        $allConditionsMet = $allConditionsMet && $currentConditionMet;
                    }

                    $canTamper = $canTamper || $allConditionsMet;
                }

                if ($canTamper) {
                    $record[$field['replace']['identifier']] = $with ?? $record[$field['replace']['identifier']];
                }
            }

            return $record;
        }, $input);

        \Drupal::logger('streamline')->debug('Tampered data: @data', [
            '@data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $data;
    }
}
