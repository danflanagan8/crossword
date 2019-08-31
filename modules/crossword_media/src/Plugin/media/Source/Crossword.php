<?php

namespace Drupal\crossword_media\Plugin\media\Source;

use Drupal\media\MediaTypeInterface;
use Drupal\media\Plugin\media\Source\File;

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

}
