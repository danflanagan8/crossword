<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field\Entity\File;
use Drupal\crossword\CrosswordFileParser;

/**
 * Plugin implementation of the 'crossword_book' formatter.
 *
 * @FieldFormatter(
 *   id = "crossword_book",
 *   label = @Translation("Crossword Puzzle (Book Style)"),
 *   field_types = {
 *     "crossword"
 *   }
 * )
 */
class CrosswordBookFormatter extends CrosswordFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as $delta => $element) {
      $elements[$delta]['#attributes']['class'][] = 'crossword-book';
    }
    return $elements;
  }

}