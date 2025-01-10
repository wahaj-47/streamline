<?php

namespace Drupal\streamline\Plugin\Step;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Step plugin manager.
 *
 * @see \Drupal\streamline\Annotation\Step
 * @see \Drupal\streamline\Plugin\Step\StepInterface
 * @see plugin_api
 */
class StepManager extends DefaultPluginManager
{

    /**
     * Constructs a StepPluginManager object.
     *
     * @param \Traversable $namespaces
     *   An object that implements \Traversable which contains the root paths
     *   keyed by the corresponding namespace to look for plugin implementations.
     * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
     *   Cache backend instance to use.
     * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
     *   The module handler to invoke the alter hook with.
     */
    public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler)
    {
        parent::__construct(
            'Plugin/Step',
            $namespaces,
            $module_handler,
            'Drupal\streamline\Plugin\Step\StepInterface',
            'Drupal\streamline\Annotation\Step'
        );

        $this->alterInfo('step');
        $this->setCacheBackend($cache_backend, 'step_plugins');
    }
}
