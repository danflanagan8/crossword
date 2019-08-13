<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field\Entity\File;
use Drupal\crossword\CrosswordFileParser;

/**
 * Plugin implementation of the 'crossword_solution' formatter.
 *
 * @FieldFormatter(
 *   id = "crossword_solution",
 *   label = @Translation("Crossword Solution"),
 *   field_types = {
 *     "crossword"
 *   }
 * )
 */
class CrosswordSolutionFormatter extends CrosswordFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {

      $parser_manager = \Drupal::service('crossword.manager.parser');
      $parser = $parser_manager->loadCrosswordFileParserFromInput($file);
      $data = $parser->parse();

      $elements[$delta] = [
        '#theme' => 'crossword_solution',
        '#content' => [
          'title' => $this->getTitle($data),
          'author' => $this->getAuthor($data),
          'notepad' => $this->getNotepad($data),
          'grid' => $this->getGrid($data),
        ],
        '#attached' => [
          'library' => [
            'crossword/crossword.solution',
          ],
        ],
        '#attributes' => [
          'class' => [],
        ],
      ];
    }
    return $elements;
  }

  protected function getGrid($data) {
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
              'class' => [
                'black',
              ],
            ],
          ];
        }
        else {
          $render_row['#content'][] = [
            '#theme' => 'crossword_square',
            '#fill' => strip_tags($square['fill']),
            '#numeral' => isset($square['numeral']) ? $square['numeral'] : NULL,
            '#attributes' => [],
          ];
        }
      }
      $render['#content'][] = $render_row;
    }

    return $render;
  }


}
