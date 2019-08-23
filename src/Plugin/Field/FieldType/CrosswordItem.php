<?php

namespace Drupal\crossword\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'crossword' field type.
 *
 * @FieldType(
 *   id = "crossword",
 *   label = @Translation("Crossword"),
 *   description = @Translation("This field stores the fid of an txt or puz crossword file."),
 *   category = @Translation("Reference"),
 *   default_widget = "file_generic",
 *   default_formatter = "file_default",
 *   list_class = "\Drupal\file\Plugin\Field\FieldType\FileFieldItemList",
 *   constraints = {
 *     "ReferenceAccess" = {},
 *     "FileValidation" = {},
 *     "CrosswordFile" = {}
 *   },
 *   cardinality = "1"
 * )
 */
class CrosswordItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'file_extensions' => 'txt puz',
      'allowed_parsers' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::fieldSettingsForm($form, $form_state);
    $form['allowed_parsers'] = [
      '#title' => $this->t('Allowed Crossword File Parsers'),
      '#description' => $this->t('Make only selected parsers available. If none are selected any crossword file parser can be used.'),
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('allowed_parsers'),
      '#options' => \Drupal::service('crossword.manager.parser')->getInstalledParsersOptionList(),
    ];
    return $form;
  }

}
