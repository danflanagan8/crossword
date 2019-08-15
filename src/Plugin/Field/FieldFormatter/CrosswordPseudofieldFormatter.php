<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\field\Entity\File;
use Drupal\crossword\CrosswordFileParser;
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
    return ["Use content.$field_name.title, content.$field_name.author,
           content.$field_name.notepad, content.$field_name.grid,
           content.$field_name.across, and content.$field_name.down in the
           template for this $referencing_type."];
  }

}
