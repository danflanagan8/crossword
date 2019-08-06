<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Plugin implementation of the 'file_default_crossword' formatter.
 *
 * @FieldFormatter(
 *   id = "file_default_crossword",
 *   label = @Translation("Generic file"),
 *   field_types = {
 *     "crossword"
 *   }
 * )
 */
class CrosswordGenericFileFormatter extends GenericFileFormatter {}
