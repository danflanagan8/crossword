<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\field\Entity\File;
use Drupal\crossword\CrosswordFileParser;
use Drupal\Core\Form\FormStateInterface;

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
  public static function defaultSettings() {
    $options['title_tag'] = 'h1';
    $options['author_tag'] = 'h2';
    $options['notepad_tag'] = 'p';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $tag_options = [
      'h1' => 'h1',
      'h2' => 'h2',
      'h3' => 'h3',
      'h4' => 'h4',
      'p' => 'p',
      'div' => 'div',
      'span' => 'span',
    ];

    $form['title_tag'] = [
      '#type' => 'select',
      '#title' => 'Title',
      '#default_value' => $this->getSetting('title_tag'),
      '#options' => $tag_options,
      '#empty_option' => $this->t("Do not render the title"),
    ];
    $form['author_tag'] = [
      '#type' => 'select',
      '#title' => 'Author',
      '#default_value' => $this->getSetting('author_tag'),
      '#options' => $tag_options,
      '#empty_option' => $this->t("Do not render the author"),
    ];
    $form['notepad_tag'] = [
      '#type' => 'select',
      '#title' => 'Notepad',
      '#default_value' => $this->getSetting('notepad_tag'),
      '#options' => $tag_options,
      '#empty_option' => $this->t("Do not render the notepad"),
    ];
    return $form;
  }

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
            'crossword/crossword.default',
          ],
          'drupalSettings' => [
            'crossword' => $data,
          ],
        ],
        '#attributes' => [
          'class' => [],
        ],
      ];
    }
    return $elements;
  }

  protected function getTitle($data) {
    if ($this->getSetting('title_tag')) {
      return [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('title_tag'),
        '#value' => $data['title'],
        '#attributes' => [
          'class' => [
            'crossword-title',
          ],
        ],
      ];
    }
  }

  protected function getAuthor($data) {
    if ($this->getSetting('author_tag') && isset($data['author'])) {
      return [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('author_tag'),
        '#value' => $data['author'],
        '#attributes' => [
          'class' => [
            'crossword-author',
          ],
        ],
      ];
    }
  }

  protected function getNotepad($data) {
    if ($this->getSetting('notepad_tag') && isset($data['notepad'])) {
      return [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('notepad_tag'),
        '#value' => nl2br($data['notepad']),
        '#attributes' => [
          'class' => [
            'crossword-notepad',
          ],
        ],
      ];
    }
  }

  protected function getAcross($data) {
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

  protected function getDown($data) {
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
            '#fill' => NULL,
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
