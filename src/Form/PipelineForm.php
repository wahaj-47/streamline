<?php

namespace Drupal\streamline\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing a pipeline.
 */
class PipelineForm extends EntityForm
{

    /**
     * Constructs an ExampleForm object.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     * The entityTypeManager.
     */
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** 
         * @var \Drupal\streamline\Entity\Pipeline $entity 
         */
        $entity = $this->entity;
        if (!$form_state->has('pipeline')) {
            $form_state->set('pipeline', ['steps' => $entity->steps()]);
        }

        $form['#tree'] = TRUE;

        // Pipeline info container
        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Pipeline Label'),
            '#required' => TRUE,
            '#default_value' => $entity->label()
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $entity->id(),
            '#machine_name' => [
                'exists' => [$this, 'exist'],
            ],
            '#disabled' => !$entity->isNew(),
        ];

        $form['interval'] = [
            '#type' => 'select',
            '#title' => $this->t('Cron interval'),
            '#description' => $this->t('Time after which pipeline will start execution.'),
            '#default_value' => $entity->interval() ?? 3600,
            '#options' => [
                60 => $this->t('1 minute'),
                300 => $this->t('5 minutes'),
                3600 => $this->t('1 hour'),
                86400 => $this->t('1 day'),
                604800 => $this->t('1 week'),
            ],
        ];

        $form['header'] = [
            '#type' => 'container',
        ];

        // Pipeline action container
        $form['header']['actions'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Add new step'),
        ];

        $form['header']['actions']['new_step_label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Step Label'),
        ];

        $form['header']['actions']['new_step_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Select a step type'),
            '#options' => $this->getAvailablePlugins(),
            '#default_value' => '',
            '#description' => $this->t('Select a step from the available options.'),
        ];

        $form['header']['actions']['add_step_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Add'),
            '#name' => 'add_step_0',
            '#submit' => [[$this, 'addStepSubmit']],
            '#ajax' => [
                'callback' => [$this, 'ajaxRebuildForm'],
                'wrapper' => 'pipeline-steps',
            ],
            '#limit_validation_errors' => [['header', 'actions', 'new_step_label'], ['header', 'actions', 'new_step_type']]
        ];

        // Pipeline steps
        $pipeline = $form_state->get('pipeline');

        $form['steps'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Steps'),
            '#collapsible' => FALSE,
            '#description' => $this->t('Manage steps in the pipeline.'),
            '#attributes' => [
                'id' => 'pipeline-steps'
            ]
        ];

        foreach ($pipeline['steps'] as $delta => $step) {
            $form['steps'][$delta] = [
                '#type' => 'details',
                '#title' => $this->t('Step: @type - @label', ['@label' => ucwords($step['label']), '@type' => strtoupper($step['type'])]),
                '#open' => TRUE,
            ];

            $plugin_manager = \Drupal::service('plugin.manager.step');
            try {

                $plugin_instance = $plugin_manager->createInstance(
                    $step['type'],
                    $step
                );
                $form_state->set('plugin_instance-' . $delta, $plugin_instance);

                // Passing in custom props via $form array
                $form['steps'][$delta]['#parents'] = ['steps', $delta];
                $form['steps'][$delta]['#delta'] = $delta;

                $form['steps'][$delta] = $plugin_instance->buildConfigurationForm($form['steps'][$delta], $form_state);
            } catch (\Exception  $e) {
                \Drupal::logger('streamline')->error('Failed to create plugin instance: @message', ['@message' => $e->getMessage()]);
            }

            // Add remove and reorder operations.
            $form['steps'][$delta]['operations'] = [
                '#type' => 'actions',
                '#value' => NULL,
                'remove' => [
                    '#type' => 'submit',
                    '#value' => $this->t('Remove'),
                    '#name' => "remove_step_$delta",
                    '#submit' => [[$this, 'removeStepSubmit']],
                    '#ajax' => [
                        'callback' => [$this, 'ajaxRebuildForm'],
                        'wrapper' => 'pipeline-steps',
                    ],
                    '#limit_validation_errors' => []
                ],
                'move_up' => [
                    '#type' => 'submit',
                    '#value' => $this->t('Move Up'),
                    '#name' => "move_up_$delta",
                    '#submit' => [[$this, 'reorderStepSubmit']],
                    '#ajax' => [
                        'callback' => [$this, 'ajaxRebuildForm'],
                        'wrapper' => 'pipeline-steps',
                    ],
                    '#limit_validation_errors' => []
                ],
                'move_down' => [
                    '#type' => 'submit',
                    '#value' => $this->t('Move Down'),
                    '#name' => "move_down_$delta",
                    '#submit' => [[$this, 'reorderStepSubmit']],
                    '#ajax' => [
                        'callback' => [$this, 'ajaxRebuildForm'],
                        'wrapper' => 'pipeline-steps',
                    ],
                    '#limit_validation_errors' => []
                ],
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        /** 
         * @var \Drupal\streamline\Entity\Pipeline $entity 
         */
        $entity = $this->entity;

        $form_state->addCleanValueKey('header');
        $form_state->addCleanValueKey('actions');

        $pipeline = $form_state->get('pipeline');
        foreach ($pipeline['steps'] as $delta => $step) {
            /**
             * @var \Drupal\streamline\Plugin\Step\StepInterface $plugin_instance  
             */
            $plugin_instance = $form_state->get('plugin_instance-' . $delta);
            $plugin_instance->save($form_state, ['steps', $delta]);
        }

        $values = $form_state->cleanValues()->getValues();

        $entity->set('interval', $values['interval']);
        $entity->set('next_execution', 0);

        if (isset($values['steps'])) {
            $entity->set('steps', $values['steps']);
        }

        $status = $entity->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addMessage($this->t('The %label Pipeline created.', [
                '%label' => $entity->label(),
            ]));
        } else {
            $this->messenger()->addMessage($this->t('The %label Pipeline updated.', [
                '%label' => $entity->label(),
            ]));
        }

        $form_state->setRedirect('entity.pipeline.collection');
    }

    /**
     * Helper function to check whether an Pipeline configuration entity exists.
     */
    public function exist($id)
    {
        $entity = $this->entityTypeManager->getStorage('pipeline')->getQuery()
            ->condition('id', $id)
            ->execute();
        return (bool) $entity;
    }

    /**
     * AJAX callback to rebuild the form.
     */
    public function ajaxRebuildForm(array &$form, FormStateInterface $form_state)
    {
        return $form['steps'];
    }

    /**
     * Submit handler to add a step.
     */
    public function addStepSubmit(array &$form, FormStateInterface $form_state)
    {
        $new_step_label = $form_state->getValue(['header', 'actions', 'new_step_label']);
        $new_step_type = $form_state->getValue(['header', 'actions', 'new_step_type']);

        $pipeline = $form_state->get('pipeline');

        $pipeline['steps'][] = [
            'label' => $new_step_label,
            'type' => $new_step_type
        ];

        $form_state->set('pipeline', $pipeline);
        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove a step.
     */
    public function removeStepSubmit(array &$form, FormStateInterface $form_state)
    {
        $pipeline = $form_state->get('pipeline');
        $trigger = $form_state->getTriggeringElement()['#name'];
        $delta = str_replace('remove_step_', '', $trigger);

        unset($pipeline['steps'][$delta]);
        $pipeline['steps'] = array_values($pipeline['steps']); // Re-index the array.

        $form_state->set('pipeline', $pipeline);
        $form_state->setRebuild(TRUE);
    }

    /**
     * Handle reordering of the steps (move up or down).
     */
    public function reorderStepSubmit(array &$form, FormStateInterface $form_state)
    {
        // Get the delta for the step being moved.
        $triggering_element = $form_state->getTriggeringElement();
        $delta = $triggering_element['#parents'][1]; // Get the delta from the parents array.

        // Retrieve the steps array from the form state.
        $pipeline = $form_state->get('pipeline');
        $pipeline_steps = $pipeline['steps'];

        // Determine if the move is up or down.
        $direction = strpos($triggering_element['#name'], 'move_up') !== FALSE ? 'up' : 'down';

        // Get the current index of the step.
        $current_index = array_search($delta, array_keys($pipeline_steps));

        // If moving up and it's not the first step, swap it with the previous step.
        if ($direction === 'up' && $current_index > 0) {
            $this->swapSteps($pipeline_steps, $current_index, $current_index - 1);
        }
        // If moving down and it's not the last step, swap it with the next step.
        elseif ($direction === 'down' && $current_index < count($pipeline_steps) - 1) {
            $this->swapSteps($pipeline_steps, $current_index, $current_index + 1);
        }

        $pipeline['steps'] = $pipeline_steps;
        $form_state->set('pipeline', $pipeline);

        // Rebuild the form to reflect the new order.
        $form_state->setRebuild(TRUE);
    }

    /**
     * Helper function to swap two steps in the pipeline.
     */
    protected function swapSteps(array &$steps, $index1, $index2)
    {
        $temp = $steps[$index1];
        $steps[$index1] = $steps[$index2];
        $steps[$index2] = $temp;
    }

    public function getValue(string|array $key, FormStateInterface $form_state)
    {
        $value = $form_state->getValue($key);

        if ($value) {
            return $value;
        }

        $value = $form_state->get($key);

        if ($value) {
            return $value;
        }

        return null;
    }

    public function getAvailablePlugins()
    {
        $plugin_manager = \Drupal::service('plugin.manager.step');
        $plugins = $plugin_manager->getDefinitions();

        $options = [];
        foreach ($plugins as $plugin_id => $plugin_definition) {
            $options[$plugin_id] = $plugin_definition['label'];
        }

        return $options;
    }
}
