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
    $clue_index = [
      'down' => 0,
      'across' => 0,
      'numeral' => 1,
    ];
    $clue_order = [];
    foreach ($grid_lines as $row_index => $grid_line) {
      $row = [
        '#theme' => 'crossword_grid_row',
        '#content' => [],
      ];
      for ($col_index = 0; $col_index < strlen($grid_line); $col_index++) {
        $fill = $grid_line[(string) $col_index];
        $classes = [
          'crossword-square',
        ];
        if ($fill == ".") {
          $classes[] = 'black';
          $row['#content'][] = [
            '#theme' => 'crossword_square',
            '#fill' => $grid_line[$col_index],
            '#attributes' => [
              'data-col' => [(string) $col_index],
              'data-row' => [(string) $row_index],
              'data-fill' => $fill,
              'class' => $classes,
            ],
          ];
        }
        else {
          $increment_numeral = FALSE;
          $numeral = NULL;
          /**
           * This will be the first square in an across clue if...
           * 1. It's the left square or to the right of a black
           * AND
           * 2. It's not the right square and the square to its right is not black.
           */
          if ($col_index == 0 || $grid_line[$col_index - 1] == ".") {
            if (isset($grid_line[$col_index + 1]) && $grid_line[$col_index + 1] !== ".") {
              $clue_index['across']++;
              $clue_order[] = 'across';
              $numeral = $clue_index['numeral'];
              $increment_numeral = TRUE;
            }
          }

          /**
           * This will be the first square in a down clue if...
           * 1. It's the top square or the below a black
           * AND
           * 2. It's not the bottom square and the square below it is not black.
           */
          if ($row_index == 0 || $grid_lines[$row_index - 1][$col_index] == ".") {
            if (isset($grid_lines[$row_index + 1][$col_index]) && $grid_lines[$row_index + 1][$col_index] !== ".") {
              $clue_index['down']++;
              $clue_order[] = 'down';
              $numeral = $clue_index['numeral'];
              $increment_numeral = TRUE;
            }
          }

          if ($increment_numeral) {
            $clue_index['numeral']++;
          }

          $row['#content'][] = [
            '#theme' => 'crossword_square',
            '#fill' => $grid_line[$col_index],
            '#attributes' => [
              'data-col' => [(string) $col_index],
              'data-row' => [(string) $row_index],
              'data-clue-index-down' => [$clue_index['down']],
              'data-clue-index-across' => [$clue_index['across']],
              'data-numeral' => [$numeral],
              'data-fill' => $fill,
              'class' => $classes,
            ],
          ];

        }

      }
      $grid['#content'][] = $row;
    }

    $across_clues_start_index = array_search("<ACROSS>", $lines) + 1;
    $down_clues_start_index = array_search("<DOWN>", $lines) + 1;
    $across_clues_number = $down_clues_start_index - $across_clues_start_index - 1;

    $across_clues = array_slice($lines, $across_clues_start_index, $across_clues_number);
    $down_clues = array_slice($lines, $down_clues_start_index); // It's ok there may be notes below...

    $down = [
      '#theme' => 'crossword_clues',
      '#content' => [],
    ];
    $across = [
      '#theme' => 'crossword_clues',
      '#content' => [],
    ];
    $down_index = 0;
    $across_index = 0;
    foreach ($clue_order as $numeral => $direction) {
      if ($direction == 'across') {
        $across['#content'][] = [
          '#theme' => 'crossword_clue',
          '#numeral' => $numeral + 1,
          '#text' => $across_clues[$across_index],
          '#attributes' => [
            'data-clue-index-across' => [$across_index],
          ],
        ];
        $across_index++;
      }
      else {
        $down['#content'][] = [
          '#theme' => 'crossword_clue',
          '#numeral' => $numeral + 1,
          '#text' => $down_clues[$down_index],
          '#attributes' => [
            'data-clue-index-down' => [$down_index],
          ],
        ];
        $down_index++;
      }
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
      'down' => $down,
      'across' => $across,
    ];
  }

}
