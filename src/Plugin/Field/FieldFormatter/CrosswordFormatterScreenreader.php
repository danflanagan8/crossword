<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'crossword_screenreader' formatter.
 *
 * @FieldFormatter(
 *   id = "crossword_screenreader",
 *   label = @Translation("Crossword Puzzle (screenreader)"),
 *   field_types = {
 *     "crossword"
 *   }
 * )
 */
class CrosswordFormatterScreenreader extends CrosswordFormatter {

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
      'solution',
      'clear',
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
      '#description' => $this->t('Include the print stylesheet from the Crossword module.'),
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
      '#prefix' => '<p>Select the html tag to use for rendering these details.</p>',
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
        'solution' => 'Solution',
        'clear' => 'Clear',
      ],
      '#default_value' => $this->getSetting('buttons')['buttons'],
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
        '#screenreader' => TRUE,
        '#content' => [
          'title' => $this->getTitle($data),
          'author' => $this->getAuthor($data),
          'notepad' => $this->getNotepad($data),
          'grid' => $this->getGrid($data),
          'across' => $this->getAcross($data),
          'down' => $this->getDown($data),
          'controls' => $this->getControls(),
        ],
        '#attached' => [
          'library' => [
            'crossword/crossword.screenreader',
            $this->getSetting('print') ? 'crossword/crossword.print' : '',
          ],
          'drupalSettings' => [
            'crossword' => [
              'data' => $data,
              'selector' => '.crossword',
            ],
          ],
        ],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }
    return $elements;
  }

}
