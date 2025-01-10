<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;
use SimpleXMLElement;

/**
 * Plugin implementation of the 'OAI-PMH fetch' step.
 *
 * @Step(
 *   id = "oaipmh-fetch",
 *   label = @Translation("Fetch via OAI-PMH step"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class OAIPMHFetch extends FetchBase implements StepInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $form['extracted_elements'] = [
            '#type' => 'textfield',
            '#title' => t('Extracted Elements'),
            '#description' => t('Comma separated list of elements to extract.'),
            '#default_value' => $this->configuration['extracted_elements'] ?? '',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     * Uses OAI-PMH for fetching data
     * OAI 2.0: http://www.openarchives.org/OAI/2.0/
     * Dublin core standard: http://purl.org/dc/elements/1.1/
     */
    public function execute($input = NULL)
    {
        $endpoint = $this->configuration["endpoint"];
        $data = [];

        do {
            $xml = simplexml_load_file($endpoint);

            if ($xml === false) {
                \Drupal::logger('streamline')->error('Error loading OAI-PMH XML file from endpoint: @endpoint', ['@endpoint' => $endpoint]);

                throw new \RuntimeException('Error loading OAI-PMH XML file');
            }

            $xml->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
            $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            $records = $xml->xpath('//oai:record');

            if ($records === false) return [];

            $extracted_elements = array_map('trim', explode(',', $this->configuration['extracted_elements']));
            $extracted_elements = array_filter($extracted_elements, fn($value) => !empty($value));

            foreach ($records as $record) {
                $metadata = $record->metadata;
                if (!empty($metadata)) {
                    $entry = [];

                    foreach ($extracted_elements as $element) {
                        $elementData = $metadata->children('oai_dc', true)->children('dc', true)->$element;
                        if ($elementData) {
                            $entry[$element] = $elementData;
                        } else {
                            $entry[$element] = "";
                        }
                    }

                    $data[] = $entry;
                }
            }

            $resumptionToken = (string)$xml->ListRecords->resumptionToken;

            if (!empty($resumptionToken)) {
                $encodedResumptionToken = urlencode($resumptionToken);
                $endpoint = $this->configuration["endpoint"] . '&resumptionToken=' . $encodedResumptionToken;
            }
        } while (!empty($resumptionToken));

        \Drupal::logger('streamline')->debug('Fetched data: @data', [
            '@data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $data;
    }
}
