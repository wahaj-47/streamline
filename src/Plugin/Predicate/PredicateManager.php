<?php

namespace Drupal\streamline\Plugin\Predicate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an Predicate plugin manager.
 *
 * @see \Drupal\streamline\Annotation\Predicate
 * @see \Drupal\streamline\Plugin\Predicate\PredicateInterface
 * @see plugin_api
 */
class PredicateManager extends DefaultPluginManager
{

    /**
     * Constructs a PredicateManager object.
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
            'Plugin/Predicate',
            $namespaces,
            $module_handler,
            'Drupal\streamline\Plugin\Predicate\PredicateInterface',
            'Drupal\streamline\Annotation\Predicate'
        );

        $this->alterInfo('predicate');
        $this->setCacheBackend($cache_backend, 'predicate_plugins');
    }
}
