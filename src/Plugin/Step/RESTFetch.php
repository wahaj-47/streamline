<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'REST fetch' step.
 *
 * @Step(
 *   id = "rest-fetch",
 *   label = @Translation("Fetch via REST step"),
 *   edit = {
 *     "editor" = "direct",
 *   },
 * )
 */
class RESTFetch extends FetchBase implements StepInterface
{

    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        $delta = $form['#delta'];

        $form['params'] = [
            '#type' => 'fieldset',
            '#title' => t('Parameters'),
            '#attributes' => ['id' => 'step-' . $delta . '-params']
        ];

        $form['params']['add_param_button'] = [
            '#type' => 'submit',
            '#value' => t('Add Param'),
            '#name' => 'step-' . $delta . '-add_param',
            '#submit' => [[get_class($this), 'addParamSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildParams'],
                'wrapper' => 'step-' . $delta . '-params',
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        $form['params']['remove_param_button'] = [
            '#type' => 'submit',
            '#value' => t('Remove Param'),
            '#name' => 'step-' . $delta . '-remove_param',
            '#submit' => [[get_class($this), 'removeParamSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildParams'],
                'wrapper' => 'step-' . $delta . '-params',
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        if (!$form_state->has('step-' . $delta . '-param-count') && isset($this->configuration['params'])) {
            $form_state->set('step-' . $delta . '-param-count', count($this->configuration['params']));
        }

        $param_count = $form_state->get('step-' . $delta . '-param-count') ?? 0;

        for ($i = 0; $i < $param_count; $i++) {
            $form['params'][$i] = [
                '#type' => 'fieldset',
                '#title' => t('Param-' . $i + 1)
            ];

            $form['params'][$i]['key'] = [
                '#type' => 'textfield',
                '#title' => t('Key'),
                '#default_value' => $this->configuration['params'][$i]['key'] ?? '',
            ];

            $form['params'][$i]['value'] = [
                '#type' => 'textfield',
                '#title' => t('Value'),
                '#default_value' => $this->configuration['params'][$i]['value'] ?? '',
                '#states' => [
                    'visible' => [
                        ':input[name="steps[' . $delta . '][params][' . $i . '][pagination_param]"]' => ['checked' => FALSE],
                    ],
                ],
            ];

            $form['params'][$i]['pagination_param'] = [
                '#type' => 'checkbox',
                '#title' => t('Pagination param'),
                '#default_value' => $this->configuration['params'][$i]['pagination_param'] ?? FALSE,
                '#states' => [
                    'visible' => [
                        ':input[name="steps[' . $delta . '][params][' . $i . '][value]"]' => ['value' => ''],
                    ],
                ],
            ];
        }

        /**
         * Does request requires authentication?
         */
        $form['requires_auth'] = [
            '#type' => 'checkbox',
            '#title' => t('Authentication required'),
            '#default_value' => $this->configuration['requires_auth'] ?? TRUE,
        ];

        /**
         * Auth Config
         */
        $form['auth'] = [
            '#type' => 'details',
            '#title' => t("Auth Config"),
            '#states' => [
                'visible' => [
                    ':input[name="steps[' . $delta . '][requires_auth]"]' => ['checked' => TRUE],
                ],
            ],
        ];

        $form['auth']['endpoint'] = [
            '#type' => 'textfield',
            '#title' => t('Authentication Endpoint'),
            '#default_value' => $this->configuration['auth']['endpoint'] ?? '',
        ];

        $form['auth']['token_param'] = [
            '#type' => 'textfield',
            '#title' => t('Access Token Param'),
            '#default_value' => $this->configuration['auth']['token_param'] ?? '',
        ];

        $form['auth']['params'] = [
            '#type' => 'fieldset',
            '#title' => t('Parameters'),
            '#attributes' => ['id' => 'step-' . $delta . '-auth-params']
        ];

        $form['auth']['params']['add_auth_param_button'] = [
            '#type' => 'submit',
            '#value' => t('Add Param'),
            '#name' => 'step-' . $delta . '-add_auth_param',
            '#submit' => [[get_class($this), 'addAuthParamSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildParams'],
                'wrapper' => 'step-' . $delta . '-auth-params',
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        $form['auth']['params']['remove_auth_param_button'] = [
            '#type' => 'submit',
            '#value' => t('Remove Param'),
            '#name' => 'step-' . $delta . '-remove_auth_param',
            '#submit' => [[get_class($this), 'removeAuthParamSubmit']],
            '#ajax' => [
                'callback' => [get_class($this), 'ajaxRebuildParams'],
                'wrapper' => 'step-' . $delta . '-auth-params',
                'delta' => $delta
            ],
            '#limit_validation_errors' => []
        ];

        if (!$form_state->has('step-' . $delta . '-auth-param-count') && isset($this->configuration['auth']['params'])) {
            $form_state->set('step-' . $delta . '-auth-param-count', count($this->configuration['auth']['params']));
        }

        $auth_param_count = $form_state->get('step-' . $delta . '-auth-param-count') ?? 0;

        for ($i = 0; $i < $auth_param_count; $i++) {
            $form['auth']['params'][$i] = [
                '#type' => 'fieldset',
                '#title' => t('Param-' . $i + 1)
            ];

            $form['auth']['params'][$i]['key'] = [
                '#type' => 'textfield',
                '#title' => t('Key'),
                '#default_value' => $this->configuration['auth']['params'][$i]['key'] ?? '',
            ];

            $form['auth']['params'][$i]['value'] = [
                '#type' => 'textfield',
                '#title' => t('Value'),
                '#default_value' => $this->configuration['auth']['params'][$i]['value'] ?? '',
            ];
        }

        return $form;
    }

    /**
     * Submit handler to add a param.
     */
    public static function addParamSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $param_count = $form_state->get('step-' . $delta . '-param-count');
        $form_state->set('step-' . $delta . '-param-count', $param_count + 1);

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove a param.
     */
    public static function removeParamSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $param_count = $form_state->get('step-' . $delta . '-param-count');
        if ($param_count > 0) {
            $form_state->set('step-' . $delta . '-param-count', $param_count - 1);
        }

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to add an Auth param.
     */
    public static function addAuthParamSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $param_count = $form_state->get('step-' . $delta . '-auth-param-count');
        $form_state->set('step-' . $delta . '-auth-param-count', $param_count + 1);

        $form_state->setRebuild(TRUE);
    }

    /**
     * Submit handler to remove an Auth param.
     */
    public static function removeAuthParamSubmit(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $delta = $triggerElement['#ajax']['delta'];

        $param_count = $form_state->get('step-' . $delta . '-auth-param-count');
        if ($param_count > 0) {
            $form_state->set('step-' . $delta . '-auth-param-count', $param_count - 1);
        }

        $form_state->setRebuild(TRUE);
    }

    /**
     * AJAX callback to rebuild the field.
     */
    public static function ajaxRebuildParams(array &$form, FormStateInterface $form_state)
    {
        $triggerElement = $form_state->getTriggeringElement();
        $parents = $triggerElement['#parents'];

        // Removing [{trigger}-field-button] from parents
        $parents = array_slice($parents, 0, -1);

        $params = &$form;
        foreach ($parents as $key) {
            $params = &$params[$key];
        }
        return $params;
    }

    /**
     * {@inheritdoc}
     * Uses REST for fetching data
     */
    public function execute($input = NULL)
    {
        /**
         * @var \GuzzleHttp\ClientInterface $http_client
         */
        $http_client = \Drupal::service('http_client');

        $endpoint = $this->configuration['endpoint'];
        $requires_auth = $this->configuration['requires_auth'];

        $token = NULL;

        if ($requires_auth) {
            $token = $this->authenticate();
        }

        $options = [
            'headers' => [
                'Cache-Control' => 'no-cache'
            ]
        ];

        if ($token != NULL) {
            $options['headers'] = ['Authorization' => 'Bearer ' . $token];
        }

        $pagination_param = NULL;
        $param_count = count($this->configuration['params']);

        for ($i = 0; $i < $param_count; $i++) {
            $key = $this->configuration['params'][$i]['key'];
            $value = $this->configuration['params'][$i]['value'];
            $is_pagination_param = $this->configuration['params'][$i]['pagination_param'];

            if ($is_pagination_param) {
                $pagination_param = $key;
                $options['query'][$key] = 0;
            } else {
                $options['query'][$key] = $value;
            }
        }

        $data = [];
        $response_data = [];
        do {
            $response = $http_client->request('GET', $endpoint, $options);
            $response_data = json_decode($response->getBody(), true);

            $data = $this->array_merge_recursive($data, $response_data);

            $options['query'][$pagination_param]++;
        } while (!$this->empty_recursive($response_data));

        \Drupal::logger('streamline')->debug('Fetched data: @data', [
            '@data' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);

        return $data;
    }

    private function authenticate()
    {
        /**
         * @var \GuzzleHttp\ClientInterface $http_client
         */
        $http_client = \Drupal::service('http_client');

        $auth_endpoint = $this->configuration['auth']['endpoint'];
        $auth_param_count = count($this->configuration['auth']['params']);

        $data = [];

        for ($i = 0; $i < $auth_param_count; $i++) {
            $key = $this->configuration['auth']['params'][$i]['key'];
            $value = $this->configuration['auth']['params'][$i]['value'];
            $data[$key] = $value;
        }

        $response = $http_client->request(
            'POST',
            $auth_endpoint,
            [
                'json' => $data
            ]
        );

        $response_data = json_decode($response->getBody(), TRUE);
        $token_param = $this->configuration['auth']['token_param'];

        $token = $response_data[$token_param];

        return $token;
    }

    private function array_merge_recursive($array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (isset($array1[$key]) && is_array($value) && is_array($array1[$key])) {
                $array1[$key] = array_merge_recursive($array1[$key], $value);
            } else {
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    private function empty_recursive($array)
    {
        if (empty($array)) {
            return true;
        }

        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                if (!$this->empty_recursive($array[$key])) {
                    return false;
                }
            }
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }
}
