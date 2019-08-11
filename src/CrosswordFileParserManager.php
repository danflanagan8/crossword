<?php

namespace Drupal\crossword;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Gathers the crossword file parser plugins.
 */
class CrosswordFileParserManager extends DefaultPluginManager implements CrosswordFileParserManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/crossword/crossword_file_parser', $namespaces, $module_handler, 'Drupal\crossword\CrosswordFileParserPluginInterface', 'Drupal\crossword\Annotation\CrosswordFileParser');
  }

  /**
   * {@inheritdoc}
   */
  public function filterApplicableDefinitions(array $definitions, $file) {
    foreach ($definitions as $definition) {
      $is_applicable = $definition['class']::isApplicable($file);
      if ($is_applicable) {
        return $definition;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadCrosswordFileParserFromInput($file) {
    $definition = $this->loadDefinitionFromInput($file);
    return $definition ? $this->createInstance($definition['id'], ['fid' => $file->id()]) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefinitionFromInput($file) {
    return $this->filterApplicableDefinitions($this->getDefinitions(), $file);
  }

}
