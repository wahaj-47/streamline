<?php

namespace Drupal\streamline;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an Pipeline entity.
 */
interface PipelineInterface extends ConfigEntityInterface
{

    /**
     * Returns the cron interval for the pipeline
     */
    public function interval();

    /**
     * Returns the next execution time for the pipeline
     */
    public function next_execution();

    /**
     * Returns the steps in the pipeline
     */
    public function steps();

    /**
     * Executes the pipeline
     */
    public function execute();
}
