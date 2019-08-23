<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

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
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    unset($options['buttons']);
    unset($options['errors']);
    unset($options['references']);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['buttons']);
    unset($form['errors']);
    unset($form['references']);
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
        '#theme' => 'crossword_solution',
        '#content' => [
          'title' => $this->getTitle($data),
          'author' => $this->getAuthor($data),
          'notepad' => $this->getNotepad($data),
          'grid' => $this->getGrid($data, TRUE),
        ],
        '#attached' => [
          'library' => [
            'crossword/crossword.solution',
          ],
        ],
        '#attributes' => [
          'class' => [],
        ],
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }
    return $elements;
  }

}
