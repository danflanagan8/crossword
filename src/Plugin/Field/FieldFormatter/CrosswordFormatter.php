<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\field\Entity\File;
use Drupal\crossword\CrosswordFileParser;

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
        '#attached' => [
          'library' => [
            'crossword/crossword',
          ],
        ],
      ];
    }
    return $elements;
  }

  private function parse($file) {
    $render = [];
    $parser = new CrosswordFileParser($file);
    $data = $parser->parse();

    $render['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $data['title'],
    ];

    $render['author'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $data['author'],
    ];

    $render['across'] = [
      '#theme' => 'crossword_clues',
      '#content' => [],
    ];
    foreach ($data['puzzle']['clues']['across'] as $across_index => $across) {
      $render['across']['#content'][] = [
        '#theme' => 'crossword_clue',
        '#text' => $across['text'],
        '#numeral' => $across['numeral'],
        '#attributes' => [
          'data-clue-index' => (string) $across_index,
        ],
      ];
    }

    $render['down'] = [
      '#theme' => 'crossword_clues',
      '#content' => [],
    ];
    foreach ($data['puzzle']['clues']['down'] as $down_index => $down) {
      $render['down']['#content'][] = [
        '#theme' => 'crossword_clue',
        '#text' => $down['text'],
        '#numeral' => $down['numeral'],
        '#attributes' => [
          'data-clue-index' => (string) $down_index,
        ],
      ];
    }

    $render['grid'] = [
      '#theme' => 'crossword_grid',
      '#content' => [],
    ];
    foreach ($data['puzzle']['grid'] as $row_index => $grid_row) {
      $render_row = [
        '#theme' => 'crossword_grid_row',
        '#content' => [],
      ];
      foreach ($grid_row as $col_index => $square) {
        if ($square['fill'] === NULL ){
          $render_row['#content'][] = [
            '#theme' => 'crossword_square',
            '#attributes' => [
              'data-col' => (string) $col_index,
              'data-row' => (string) $row_index,
              'class' => [
                'black',
              ],
            ],
          ];
        }
        else {
          $render_row['#content'][] = [
            '#theme' => 'crossword_square',
            '#fill' => $square['fill'],
            '#numeral' => $square['numeral'],
            '#attributes' => [
              'data-col' => (string) $col_index,
              'data-row' => (string) $row_index,
              'data-clue-index-across' => (string) $square['across']['index'],
              'data-clue-index-down' => (string) $square['down']['index'],
              'data-numeral' => $square['numeral'],
              'data-fill' => $square['fill'],
            ],
          ];
        }
      }
      $render['grid']['#content'][] = $render_row;
    }

    return $render;
  }

}
