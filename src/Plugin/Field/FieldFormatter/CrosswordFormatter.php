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
    $options['print'] = TRUE;
    $options['details'] = [
      'title_tag' => 'h1',
      'author_tag' => 'h2',
      'notepad_tag' => 'p',
    ];
    $options['buttons']['buttons'] = [
      'cheat',
      'solution',
      'clear',
      'undo',
      'redo',
    ];
    $options['errors'] = [
      'show' => TRUE,
      'checked' => FALSE,
    ];
    $options['references'] = [
      'show' => TRUE,
      'checked' => TRUE,
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $field_name = $this->fieldDefinition->getName();

    $tag_options = [
      'h1' => 'h1',
      'h2' => 'h2',
      'h3' => 'h3',
      'h4' => 'h4',
      'p' => 'p',
      'div' => 'div',
      'span' => 'span',
    ];

    $form['print'] = [
      '#type' => 'checkbox',
      '#title' => 'Print Styles',
      '#default_value' => $this->getSetting('print'),
      '#description' => 'Include the print stylesheet from the Crossword module.',
    ];
    $form['details'] = [
      '#type' => 'fieldset',
      '#title' => 'Crossword Details',
      '#tree' => TRUE,
    ];
    $form['details']['title_tag'] = [
      '#type' => 'select',
      '#title' => 'Title',
      '#default_value' => $this->getSetting('details')['title_tag'],
      '#options' => $tag_options,
      '#empty_option' => $this->t("Do not render the title"),
      '#prefix' => '<p>Select the html tag to usee for rendering these details.</p>',
    ];
    $form['details']['author_tag'] = [
      '#type' => 'select',
      '#title' => 'Author',
      '#default_value' => $this->getSetting('details')['author_tag'],
      '#options' => $tag_options,
      '#empty_option' => $this->t("Do not render the author"),
    ];
    $form['details']['notepad_tag'] = [
      '#type' => 'select',
      '#title' => 'Notepad',
      '#default_value' => $this->getSetting('details')['notepad_tag'],
      '#options' => $tag_options,
      '#empty_option' => $this->t("Do not render the notepad"),
    ];

    $form['buttons'] = [
      '#type' => 'fieldset',
      '#title' => 'Buttons',
      '#tree' => TRUE,
    ];

    $form['buttons']['buttons'] = [
      '#type' => 'checkboxes',
      '#title' => 'Buttons',
      '#prefix' => '<p>Check all the buttons that should be available</p>',
      '#options' => [
        'cheat' => 'Cheat',
        'solution' => 'Solution',
        'clear' => 'Clear',
        'undo' => 'Undo',
        'redo' => 'Redo',
      ],
      '#default_value' => $this->getSetting('buttons')['buttons'],
    ];
    $form['errors'] = [
      '#type' => 'fieldset',
      '#title' => 'Show Errors checkbox',
      '#tree' => TRUE,
      'show' => [
        '#type' => 'checkbox',
        '#title' => 'Display',
        '#default_value' => $this->getSetting('errors')['show'],
      ],
      'checked' => [
        '#type' => 'checkbox',
        '#title' => 'Check by default',
        '#default_value' => $this->getSetting('errors')['checked'],
        '#states' => array(
          'visible' => array(
            ":input[name='fields[$field_name][settings_edit_form][settings][errors][show]']" => array('checked' => TRUE),
          ),
        ),
      ],
    ];
    $form['references'] = [
      '#type' => 'fieldset',
      '#title' => 'Show References checkbox',
      '#tree' => TRUE,
      'show' => [
        '#type' => 'checkbox',
        '#title' => 'Display',
        '#default_value' => $this->getSetting('references')['show'],
      ],
      'checked' => [
        '#type' => 'checkbox',
        '#title' => 'Check by default',
        '#default_value' => $this->getSetting('references')['checked'],
        '#states' => array(
          'visible' => array(
            ":input[name='fields[$field_name][settings_edit_form][settings][references][show]']" => array('checked' => TRUE),
          ),
        ),
      ],
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
          'controls' => $this->getControls(),
        ],
        '#attached' => [
          'library' => [
            'crossword/crossword.default',
            $this->getSetting('print') ? 'crossword/crossword.print' : '',
          ],
          'drupalSettings' => [
            'crossword' => [
              'data' => $data,
              'selector' => '.crossword',
            ],
          ],
        ],
        '#attributes' => [
          'class' => [
            $this->getSetting('errors')['show'] && $this->getSetting('errors')['checked'] ? 'show-errors' : '',
            $this->getSetting('references')['show'] && $this->getSetting('references')['checked'] ? 'show-references' : '',
          ],
        ],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }
    return $elements;
  }

  protected function getTitle($data) {
    if ($this->getSetting('details')['title_tag']) {
      return [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('details')['title_tag'],
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
    if ($this->getSetting('details')['author_tag'] && isset($data['author'])) {
      return [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('details')['author_tag'],
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
    if ($this->getSetting('details')['notepad_tag'] && isset($data['notepad'])) {
      return [
        '#type' => 'html_tag',
        '#tag' => $this->getSetting('details')['notepad_tag'],
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
        ],
      ];
    }
    return $render;
  }

  protected function getGrid($data, $show_fill = FALSE) {
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
            '#fill' => $show_fill ? strtoupper($square['fill']) : NULL,
            '#numeral' => isset($square['numeral']) ? $square['numeral'] : NULL,
            '#attributes' => [
              'data-col' => (string) $col_index,
              'data-row' => (string) $row_index,
              'data-clue-index-across' => (string) $square['across']['index'],
              'data-clue-index-down' => (string) $square['down']['index'],
              'data-numeral' => isset($square['numeral']) ? $square['numeral'] : NULL,
              'data-fill' => $square['fill'],
              'data-circle' => $square['circle'],
              'data-rebus' => $square['rebus'],
            ],
          ];
        }
      }
      $render['#content'][] = $render_row;
    }

    return $render;
  }

  protected function getControls() {
    return [
      '#theme' => 'crossword_controls',
      '#content' => [
        'errors' => $this->getShowErrorsCheckbox(),
        'references' => $this->getShowReferencesCheckbox(),
        'cheat' => $this->getButton('cheat'),
        'solution' => $this->getButton('solution'),
        'clear' => $this->getButton('clear'),
        'undo' => $this->getButton('undo'),
        'redo' => $this->getButton('redo'),
      ],
    ];
  }

  protected function getShowErrorsCheckbox() {
    if ($this->getSetting('errors')['show']) {
      return [
        '#type' => 'checkbox',
        '#attributes' => [
          'class' => [
            'show-errors',
          ],
          'checked' => $this->getSetting('errors')['checked'] ? "checked" : NULL,
          'name' => 'show-errors',
          'id' => 'show-errors',
        ],
        '#children' => [
          '#type' => 'label',
          '#title' => 'Show Errors',
          '#attributes' => [
            'for' => 'show-errors',
          ],
        ],
      ];
    }
  }

  protected function getShowReferencesCheckbox() {
    if ($this->getSetting('references')['show']) {
      return [
        '#type' => 'checkbox',
        '#attributes' => [
          'class' => [
            'show-references',
          ],
          'checked' => $this->getSetting('references')['checked'] ? "checked" : NULL,
          'name' => 'show-references',
          'id' => 'show-references',
        ],
        '#children' => [
          '#type' => 'label',
          '#title' => 'Show References',
          '#attributes' => [
            'for' => 'show-references',
          ],
        ],
      ];
    }
  }

  protected function getButton($name) {
    if ($this->getSetting('buttons')['buttons'][$name]) {
      return [
        '#type' => 'html_tag',
        '#tag' => 'button',
        '#value' => $name,
        '#attributes' => [
          'class' => [
            "button-$name",
          ],
        ],
      ];
    }
  }
}
