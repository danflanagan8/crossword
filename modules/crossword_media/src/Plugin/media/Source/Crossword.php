<?php

namespace Drupal\crossword_media\Plugin\media\Source;

use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;
use Drupal\file\FileInterface;
use Drupal\crossword\CrosswordImageFactory;

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
class Crossword extends File {

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
      // Check if the existing preview is older than the file itself.
      if (filemtime($file->getFileUri()) <= filemtime($destination_uri)) {
        // The existing preview can be used, nothing to do.
        return $destination_uri;
      }
      else {
        // Delete the existing but out-of-date thumbnail.
        file_unmanaged_delete($destination_uri);
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
    $output_path = 'public://crossword';
    $filename = "{$file->id()}-thumbnail.jpg";
    return $output_path . '/' . $filename;
  }

  /**
   * Creates the thumbnail image.
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
    return imagejpeg($image, $destination_uri, 75);
  }

}
