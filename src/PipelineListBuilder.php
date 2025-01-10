<?php

namespace Drupal\streamline;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;

/**
 * Provides a listing of Pipeline configuration entities.
 */
class PipelineListBuilder extends ConfigEntityListBuilder
{

    /**
     * {@inheritdoc}
     */
    public function buildHeader()
    {
        $header['label'] = $this->t('Pipeline Name');
        $header['id'] = $this->t('Machine Name');

        return $header + parent::buildHeader();
    }

    /**
     * {@inheritdoc}
     */
    public function buildRow(EntityInterface $entity)
    {
        /** @var \Drupal\streamline\Entity\Pipeline $entity */
        $row['label'] = $entity->label();
        $row['id'] = $entity->id();

        return $row + parent::buildRow($entity);
    }

    public function getOperations(EntityInterface $entity)
    {
        $operations = parent::getOperations($entity);

        if ($entity->access('execute') && $entity->hasLinkTemplate('execute-form')) {
            $execute_url = $this->ensureDestination($entity->toUrl('execute-form'));
            if (!empty($entity->label())) {
                $label = $this->t('Execute @entity_label', ['@entity_label' => $entity->label()]);
            } else {
                $label = $this->t('Execute @entity_bundle @entity_id', ['@entity_bundle' => $entity->bundle(), '@entity_id' => $entity->id()]);
            }
            $attributes = $execute_url->getOption('attributes') ?: [];
            $attributes += ['aria-label' => $label];
            $execute_url->setOption('attributes', $attributes);

            $operations['execute'] = [
                'title' => $this->t('Execute'),
                'weight' => 100,
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 880,
                    ]),
                ],
                'url' => $execute_url,
            ];
        }

        return $operations;
    }
}
