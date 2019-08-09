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

      $parser = new CrosswordFileParser($file);
      $data = $parser->parse();

      $elements[$delta] = [
        '#theme' => 'crossword',
        '#content' => [
          'title' => $this->getTitle($data),
          'author' => $this->getAuthor($data),
          'notepad' => $this->getNotepad($data),
          'across' => $this->getAcross($data),
          'down' => $this->getDown($data),
          'grid' => $this->getGrid($data),
        ],
        '#attached' => [
          'library' => [
            'crossword/crossword',
          ],
          'drupalSettings' => [
            'crossword' => $data,
          ],
        ],
      ];
    }
    return $elements;
  }

  private function getTitle($data) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'h1',
      '#value' => $data['title'],
      '#attributes' => [
        'class' => [
          'crossword-title',
        ],
      ],
    ];
  }

  private function getAuthor($data) {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $data['author'],
      '#attributes' => [
        'class' => [
          'crossword-author',
        ],
      ],
    ];
  }

  private function getNotepad($data) {
    if ($data['notepad']) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => nl2br($data['notepad']),
        '#attributes' => [
          'class' => [
            'crossword-notepad',
          ],
        ],
      ];
    }
  }

  private function getAcross($data) {
    $render = [
      '#theme' => 'crossword_clues',
      '#content' => [],
      '#direction' => 'across',
      '#attributes' => [
        'class' => ['across'],
      ],
    ];
    foreach ($data['puzzle']['clues']['across'] as $across_index => $across) {
      $render['#content'][] = [
        '#theme' => 'crossword_clue',
        '#text' => $across['text'],
        '#numeral' => $across['numeral'],
        '#attributes' => [
          'data-clue-index-across' => (string) $across_index,
          'data-clue-numeral-across' => $across['numeral'],
          'data-clue-references' => json_encode($across['references']),
        ],
      ];
    }
    return $render;
  }

  private function getDown($data) {
    $render = [
      '#theme' => 'crossword_clues',
      '#content' => [],
      '#direction' => 'down',
      '#attributes' => [
        'class' => ['down'],
      ],
    ];
    foreach ($data['puzzle']['clues']['down'] as $down_index => $down) {
      $render['#content'][] = [
        '#theme' => 'crossword_clue',
        '#text' => $down['text'],
        '#numeral' => $down['numeral'],
        '#attributes' => [
          'data-clue-index-down' => (string) $down_index,
          'data-clue-numeral-down' => $down['numeral'],
          'data-clue-references' => json_encode($down['references']),
        ],
      ];
    }
    return $render;
  }

  private function getGrid($data) {
    $render = [
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
            '#numeral' => isset($square['numeral']) ? $square['numeral'] : NULL,
            '#attributes' => [
              'data-col' => (string) $col_index,
              'data-row' => (string) $row_index,
              'data-clue-index-across' => (string) $square['across']['index'],
              'data-clue-index-down' => (string) $square['down']['index'],
              'data-numeral' => isset($square['numeral']) ? $square['numeral'] : NULL,
              'data-fill' => $square['fill'],
              'data-circle' => $square['fill'] != strtoupper($square['fill']),
              'data-rebus' => isset($square['numeral']) ? $square['numeral'] : NULL,
            ],
          ];
        }
      }
      $render['#content'][] = $render_row;
    }

    return $render;
  }

}
