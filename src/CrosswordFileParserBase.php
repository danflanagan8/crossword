<?php

namespace Drupal\crossword;

use Drupal\Core\Plugin\PluginBase;
use Drupal\crossword\CrosswordFileParserPluginInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


abstract class CrosswordFileParserBase extends PluginBase implements CrosswordFileParserPluginInterface, ContainerFactoryPluginInterface {

  /**
   * cache
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Create a plugin with the given input.
   *
   * @param string $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @throws \Exception
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->file = File::load($configuration['fid']);
    if (!static::isApplicable($this->file)) {
      throw new \Exception('Chosen crossword file parser cannot parse this file.');
    }
    $this->cache = $cache;
    $this->contents = file_get_contents($this->file->getFileUri());
    $this->contents = trim($this->contents);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.crossword')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($file) {
    return FALSE;
  }

  /**
   * Plugins that extend this base should have their own getData function.
   * The parse function is final so that caching and the crossword_alter hook
   * are standardized.
   */
  final public function parse() {

    $cached = $this->cache->get($this->file->id());
    if (isset($cached->data['data'])) {
      $data = $cached->data['data'];
    }
    else {
      $data = $this->getData();
      $this->cache->set($this->file->id(), ["data" => $data], CacheBackendInterface::CACHE_PERMANENT, $this->file->getCacheTags());
    }

    \Drupal::moduleHandler()->alter('crossword_parse', $data, $this->contents);

    return $data;
  }

  protected function getData() {
    return [];
  }



}
