<?php

namespace Drupal\crossword\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'crossword' field type.
 *
 * @FieldType(
 *   id = "crossword",
 *   label = @Translation("Crossword"),
 *   description = @Translation("This field stores the fid of an txt or puz crossword file."),
 *   category = @Translation("Reference"),
 *   default_widget = "file_generic",
 *   default_formatter = "file_default",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}, "CrosswordFile" = {}}
 * )
 */
class CrosswordItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'file_extensions' => 'txt',
    ] + parent::defaultFieldSettings();
  }

}
