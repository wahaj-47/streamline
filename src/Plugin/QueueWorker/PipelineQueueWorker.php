<?php

namespace Drupal\streamline\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Processes pipeline entities for execution.
 *
 * @QueueWorker(
 *   id = "pipeline_queue_worker",
 *   title = @Translation("Pipeline queue worker"),
 *   cron = {"time" = 60}
 * )
 */
class PipelineQueueWorker extends QueueWorkerBase
{

    /**
     * {@inheritdoc}
     */
    public function processItem($data)
    {
        // Ensure the loaded entity exists and is valid.
        if (isset($data['id'])) {

            /**
             * @var \Drupal\streamline\Entity\Pipeline $entity
             */
            $entity = \Drupal::entityTypeManager()->getStorage('pipeline')->load($data['id']);
            $entity->execute();
        }
    }
}
