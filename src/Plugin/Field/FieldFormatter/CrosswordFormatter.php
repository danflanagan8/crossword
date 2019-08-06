<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\field\Entity\File;
/**
 * Plugin implementation of the 'crossword' formatter.
 *
 * @FieldFormatter(
 *   id = "crossword",
 *   label = @Translation("Crossword Puzzle"),
 *   field_types = {
 *     "crossword"
 *   }
 * )
 */
class CrosswordFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $elements[$delta] = [
        '#theme' => 'crossword',
        '#content' => $this->parse($file),
      ];
    }
    return $elements;
  }

  private function parse($file) {
    $contents = file_get_contents($file->getFileUri());
    $across = [];
    $down = [];
    $title = NULL;
    $author = NULL;

    //turn the text into an array. Each line is an array item
    $lines = explode("\n", $contents);
    $title = $lines[array_search("<TITLE>", $lines) + 1];
    $author = $lines[array_search("<AUTHOR>", $lines) + 1];

    $grid = [
      '#theme' => 'crossword_grid',
      '#content' => [],
    ];
    $grid_start_index = array_search("<GRID>", $lines) + 1;
    $after_grid_index = array_search("<REBUS>", $lines) > -1 ? array_search("<REBUS>", $lines) : array_search("<ACROSS>", $lines);
    $number_of_rows = $after_grid_index - $grid_start_index;
    $grid_lines = array_slice($lines, $grid_start_index, $number_of_rows);
    foreach ($grid_lines as $row_index => $grid_line) {
      $row = [
        '#theme' => 'crossword_grid_row',
        '#content' => [],
      ];
      for ($col_index = 0; $col_index < strlen($grid_line); $col_index++) {
        $row['#content'][] = [
          '#theme' => 'crossword_square',
          '#fill' => $grid_line[$col_index],
          '#attributes' => [
            'data-col' => [(string) $col_index],
            'data-row' => [(string) $row_index],
            'data-fill' => $grid_line[(string) $col_index],
            'class' => ['crossword-square'],
          ],
        ];
      }
      $grid['#content'][] = $row;
    }

    return [
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h1',
        '#value' => $title,
      ],
      'author' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $author,
      ],
      'grid' => $grid,
    ];
  }

}
