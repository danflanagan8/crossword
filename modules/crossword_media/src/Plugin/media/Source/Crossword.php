<?php

namespace Drupal\crossword_media\Plugin\media\Source;

use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\file\FileInterface;
use Drupal\crossword\CrosswordImageFactory;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystem;

/**
 * Crossword media source.
 *
 * @MediaSource(
 *   id = "crossword",
 *   label = @Translation("Crossword"),
 *   description = @Translation("Use local crossword files for reusable media."),
 *   default_thumbnail_filename = "generic.png",
 *   allowed_field_types = {"crossword"},
 * )
 */
class Crossword extends File implements ContainerFactoryPluginInterface {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
   protected $fileSystem;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_manager, ConfigFactoryInterface $config_factory, FileSystem $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('config.factory'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    return parent::createSourceField($type)->set('settings', ['file_extensions' => 'txt puz']);
  }

  /**
   * Gets the thumbnail image URI based on a file entity.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   *
   * @return string
   *   File URI of the thumbnail image or NULL if there is no specific icon.
   */
  protected function getThumbnail(FileInterface $file) {

    $destination_uri = $this->getDestinationURI($file);
    if (file_exists($destination_uri)) {
      // Check if the existing thumbnail is older than the file itself.
      if (filemtime($file->getFileUri()) <= filemtime($destination_uri)) {
        // The existing thumbnail can be used, nothing to do.
        return $destination_uri;
      }
      else {
        // Delete the existing but out-of-date thumbnail.
        $this->fileSystem->delete($destination_uri);
        image_path_flush($destination_uri);
      }
    }
    if ($this->createThumbnail($file, $destination_uri)) {
      return $destination_uri;
    }
  }

  /**
   * Gets the destination URI of the file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file that is being converted.
   *
   * @return string
   *   The destination URI.
   */
  protected function getDestinationURI(FileInterface $file) {
    $output_path = file_default_scheme() . '://crossword';
    $filename = "{$file->id()}-thumbnail.jpg";
    return $output_path . '/' . $filename;
  }

  /**
   * Creates and saves the thumbnail image.
   *
   * @param \Drupal\file\FileInterface $file
   *   A file entity.
   * @param string $destination_uri
   *   The destination URI.
   *
   * @return boolean
   *   Returns true on success, false on failure.
   */
  protected function createThumbnail(FileInterface $file, $destination_uri) {
    $image_factory = new CrosswordImageFactory($file);
    $image = $image_factory->getThumbnail();
    ob_start();
    imagejpeg($image);
    $image_data = ob_get_clean();
    $directory = $this->fileSystem->dirname($destination_uri);
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    return $this->fileSystem->saveData($image_data, $destination_uri, FILE_EXISTS_REPLACE);
  }

}
