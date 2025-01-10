<?php

namespace Drupal\streamline\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\streamline\PipelineInterface;

/**
 * Defines a Pipeline configuration entity.
 *
 * @ConfigEntityType(
 *     id = "pipeline",
 *     label = @Translation("Pipeline"),
 *     handlers = {
 *         "list_builder" = "Drupal\streamline\PipelineListBuilder",
 *         "form" = {
 *             "add" = "Drupal\streamline\Form\PipelineForm",
 *             "edit" = "Drupal\streamline\Form\PipelineForm",
 *             "delete" = "Drupal\streamline\Form\PipelineDeleteForm",
 *             "execute" = "Drupal\streamline\Form\PipelineExecuteForm"
 *         }
 *     },
 *     config_prefix = "pipeline",
 *     admin_permission = "administer site configuration",
 *     entity_keys = {
 *         "id" = "id",
 *         "label" = "label",
 *     },
 *     config_export = {
 *         "id",
 *         "label",
 *         "interval",
 *         "next_execution",
 *         "steps",
 *     },
 *     links = {
 *         "add-form" = "/admin/config/development/streamline/add",
 *         "edit-form" = "/admin/config/development/streamline/{pipeline}",
 *         "delete-form" = "/admin/config/development/streamline/{pipeline}/delete",
 *         "execute-form" = "/admin/config/development/streamline/{pipeline}/execute"
 *     }
 * )
 */
class Pipeline extends ConfigEntityBase implements PipelineInterface
{
    /**
     * The ID of the pipeline.
     *
     * @var string
     */
    protected $id;

    /**
     * The label of the pipeline.
     *
     * @var string
     */
    protected $label;

    /**
     * Cron interval
     * 
     * @var int
     */
    protected $interval;

    public function interval()
    {
        return $this->get('interval');
    }

    /**
     * Next execution timestamp
     * 
     * @var int
     */
    protected $next_execution;

    public function next_execution()
    {
        return $this->get('next_execution');
    }

    /**
     * Steps in the pipeline.
     *
     * @var array
     */
    protected $steps = [];

    public function steps()
    {
        return $this->get('steps');
    }

    /**
     * Executes the pipeline
     */
    public function execute()
    {
        $steps = $this->steps();

        $input = NULL;
        foreach ($steps as $step) {
            $plugin_manager = \Drupal::service('plugin.manager.step');

            /** 
             * @var \Drupal\streamline\Plugin\Step\StepInterface $plugin_instance
             */
            $plugin_instance = $plugin_manager->createInstance(
                $step['type'],
                $step
            );

            $input = $plugin_instance->execute($input);
        }
    }
}
