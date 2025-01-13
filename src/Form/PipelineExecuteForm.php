<?php

namespace Drupal\streamline\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an Pipeline.
 */

class PipelineExecuteForm extends EntityConfirmFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t('Run pipeline %name?', ['%name' => $this->entity->label()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('entity.pipeline.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText()
    {
        return $this->t('Execute');
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->t('The pipeline execution will be queued and executed in the background. Continue?');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $queue = \Drupal::service('queue')->get('pipeline_queue_worker');

        /**
         * @var \Drupal\streamline\PipelineInterface $entity
         */
        $entity = $this->entity;

        $interval = $entity->interval();
        $interval = !empty($interval) ? $interval : 3600;

        $request_time = \Drupal::time()->getRequestTime();

        $entity->set('next_execution', $request_time + $interval);
        $queue->createItem(['id' => $entity->id()]);

        $this->messenger()->addMessage($this->t('Pipeline %label execution has been queued.', ['%label' => $this->entity->label()]));

        $form_state->setRedirectUrl($this->getCancelUrl());
    }
}
