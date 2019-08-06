<?php

namespace Drupal\crossword\Plugin\Field\FieldWidget;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;

/**
 * Plugin implementation of the 'file_generic_crossword' widget.
 *
 * @FieldWidget(
 *   id = "file_generic_crossword",
 *   label = @Translation("File"),
 *   field_types = {
 *     "crossword"
 *   }
 * )
 */
class CrosswordFileWidget extends FileWidget {}
