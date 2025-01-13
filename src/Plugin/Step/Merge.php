<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Merge' step.
 *
 * @Step(
 *   id = "merge",
 *   label = @Translation("Merge"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class Merge extends StepBase implements StepInterface
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

        $form['merge_sets'] = [
            '#type' => 'fieldset',
            '#title' => t('Merge set'),
            '#collapsible' => FALSE,
            '#description' => t('Add merge set.'),
            '#attributes' => [
                'id' => 'step-' . $delta
            ],
        ];

        $form['merge_sets']['actions']['add_merge_set_button'] = [
            '#type' => 'submit',
            '#value' => t('Add Merge Set'),
            '#name' => 'add_merge_set_' . $delta,
            '#submit' => [[get_class($this), 'addMergeSetSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildStep'],
                'wrapper' => 'step-' . $delta,
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        $form['merge_sets']['actions']['remove_merge_set_button'] = [
            '#type' => 'submit',
            '#value' => t('Remove Merge Set'),
            '#name' => 'remove_merge_set_' . $delta,
            '#submit' => [[get_class($this), 'removeMergeSetSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildStep'],
                'wrapper' => 'step-' . $delta,
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        if (!$form_state->has('step-' . $delta . '-merge-sets-count') && isset($this->configuration['merge_sets'])) {
            $form_state->set('step-' . $delta . '-merge-sets-count', count($this->configuration['merge_sets']));
        }

        $merge_sets_count = $form_state->get('step-' . $delta . '-merge-sets-count') ?? 0;

        for ($i = 0; $i < $merge_sets_count; $i++) {
            $form['merge_sets'][$i] = [
                '#type' => 'details',
                '#title' => 'Merge Set-' . ($i + 1),
                '#open' => FALSE,
            ];

            $form['merge_sets'][$i]['merged_elements'] = [
                '#type' => 'textarea',
                '#title' => t('Merged Elements'),
                '#description' => t('Comma separated list of elements to merge.'),
                '#default_value' => $this->configuration['merge_sets'][$i]['merged_elements'] ?? '',
            ];

            $form['merge_sets'][$i]['merged_into'] = [
                '#type' => 'textfield',
                '#title' => t('Into'),
                '#description' => t('The field to merge into.'),
                '#default_value' => $this->configuration['merge_sets'][$i]['merged_into'] ?? '',
            ];

            $form['merge_sets'][$i]['separator'] = [
                '#type' => 'textfield',
                '#title' => t('Separator'),
                '#default_value' => $this->configuration['merge_sets'][$i]['separator'] ?? '<sep>',
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(FormStateInterface $form_state, array $parent)
    {
        parent::save($form_state, $parent);
        $form_state->addCleanValueKey(array_merge($parent, ['merge_sets', 'actions']));
        $form_state->addCleanValueKey(array_merge($parent, ['merge_sets', 'actions', 'remove_merge_set_button']));
        $form_state->addCleanValueKey(array_merge($parent, ['merge_sets', 'actions', 'add_merge_set_button']));
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
    public static function addMergeSetSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $field_count = $form_state->get('step-' . $delta . '-merge-sets-count');
        $form_state->set('step-' . $delta . '-merge-sets-count', $field_count + 1);

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove a field.
     */
    public static function removeMergeSetSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $field_count = $form_state->get('step-' . $delta . '-merge-sets-count');
        if ($field_count > 0) {
            $form_state->set('step-' . $delta . '-merge-sets-count', $field_count - 1);
        }

        $form_state->setRebuild(TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($input = NULL)
    {
        if (empty($input)) {
            \Drupal::logger('streamline')->error('Merging fields error: $input is empty');
            return NULL;
        }


        $merge_sets = $this->configuration['merge_sets'];
        $data = array_map(function ($record) use ($merge_sets) {

            foreach ($merge_sets as $merge_set) {
                $merged_elements = array_map('trim', explode(',', $merge_set['merged_elements']));
                $merged_elements = array_filter($merged_elements, fn($value) => !empty($value));

                // Converting wildcards to regular expressions
                $merged_elements = array_map(function ($element) {
                    // Escape dots and replace * with regex equivalent.
                    $regex = preg_quote($element, '/');

                    // Replace '*' with '.*' for regex.
                    $regex = str_replace('\*', '.*', $regex);

                    return "/^$regex$/";
                }, $merged_elements);

                $merged_into = $merge_set['merged_into'];
                $separator = $merge_set['separator'];

                foreach ($record as $key => $value) {
                    foreach ($merged_elements as $regex) {
                        if (preg_match($regex, $key)) {
                            if (isset($record[$merged_into])) {
                                $record[$merged_into] = $record[$merged_into] . $separator . $value;
                            } else {
                                $record[$merged_into] = $value;
                            }
                            unset($record[$key]);
                            break;
                        }
                    }
                }
            }

            return $record;
        }, $input);

        \Drupal::logger('streamline')->debug('Merged data: @data', [
            '@data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $data;
    }
}
