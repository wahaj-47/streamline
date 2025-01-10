<?php

namespace Drupal\streamline\Plugin\Processor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Processor plugin manager.
 *
 * @see \Drupal\streamline\Annotation\Processor
 * @see \Drupal\streamline\Plugin\Processor\ProcessorInterface
 * @see plugin_api
 */
class ProcessorPluginManager extends DefaultPluginManager
{

    /**
     * Constructs a ProcessorPluginManager object.
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
            'Plugin/Processor',
            $namespaces,
            $module_handler,
            'Drupal\streamline\Plugin\Processor\ProcessorInterface',
            'Drupal\streamline\Annotation\Processor'
        );

        $this->alterInfo('processor');
        $this->setCacheBackend($cache_backend, 'processor_plugins');
    }
}
