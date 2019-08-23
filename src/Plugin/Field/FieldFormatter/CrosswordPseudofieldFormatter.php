<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'crossword_pseudofield' formatter.
 *
 * @FieldFormatter(
 *   id = "crossword_pseudofield",
 *   label = @Translation("Crossword Puzzle (pseudofields)"),
 *   field_types = {
 *     "crossword"
 *   }
 * )
 */
class CrosswordPseudofieldFormatter extends CrosswordFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    unset($options['print']);
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    unset($form['print']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $files = $this->getEntitiesToView($items, $langcode);
    if (empty($files)) {
      return;
    }
    $file = $files[0];

    $parser_manager = \Drupal::service('crossword.manager.parser');
    $parser = $parser_manager->loadCrosswordFileParserFromInput($file);
    $data = $parser->parse();

    $elements = [
      'title' => $this->getTitle($data),
      'author' => $this->getAuthor($data),
      'notepad' => $this->getNotepad($data),
      'across' => $this->getAcross($data),
      'down' => $this->getDown($data),
      'grid' => $this->getGrid($data),
      'controls' => $this->getControls(),
      '#attached' => [
        'library' => [
          'crossword/crossword.default',
        ],
        'drupalSettings' => [
          'crossword' => [
            'data' => $data,
            'selector' => 'body',
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
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    // Default the language to the current content language.
    if (empty($langcode)) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    }
    $elements = $this->viewElements($items, $langcode);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $field_name = $this->fieldDefinition->getName();
    $referencing_type = $this->fieldDefinition->getTargetEntityTypeId();
    $return = [];
    $return[] = "Use these variables in your $referencing_type template:";
    $return[] = "content.$field_name.title";
    $return[] = "content.$field_name.author";
    $return[] = "content.$field_name.notepad";
    $return[] = "content.$field_name.grid";
    $return[] = "content.$field_name.across";
    $return[] = "content.$field_name.down";
    $return[] = "content.$field_name.controls";
    return $return;
  }

}
