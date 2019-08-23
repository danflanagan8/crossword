<?php

namespace Drupal\crossword\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'crossword_file_download_link' formatter.
 *
 * This may be useful for image and file fields too.
 *
 * @FieldFormatter(
 *   id = "crossword_file_download_link",
 *   label = @Translation("File Download Link"),
 *   field_types = {
 *     "file",
 *     "image",
 *     "crossword",
 *   }
 * )
 */
class CrosswordFileDownloadLink extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();
    $options['link_text'] = 'Download';
    $options['new_tab'] = TRUE;
    $options['force_download'] = TRUE;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $element['link_text'] = [
      '#type' => 'textfield',
      '#title' => 'Link Text',
      '#default_value' => $this->getSetting('link_text'),
      '#description' => $this->t('This text is linked to the file'),
      '#required' => TRUE,
    ];
    $element['new_tab'] = [
      '#type' => 'checkbox',
      '#title' => 'Open file in new tab',
      '#default_value' => $this->getSetting('new_tab'),
    ];
    $element['force_download'] = [
      '#type' => 'checkbox',
      '#title' => 'Force Download',
      '#default_value' => $this->getSetting('force_download'),
      '#description' => $this->t('This adds the <i>download</i> attribute to the link, which works in many modern browsers.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = ['Link text: ' . $this->getSetting('link_text')];
    if ($this->getSetting('new_tab')) {
      $summary[] = 'Open in new tab';
    }
    if ($this->getSetting('force_download')) {
      $summary[] = 'Force download';
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {

      // Options for the link, like classes.
      $file_type_explosion = explode("/", $file->getMimeType());
      $file_type = end($file_type_explosion);
      $options = [
        'attributes' => [
          'class' => [
            'file-download',
            'file-download-' . $file_type,
          ],
        ],
      ];
      if ($this->getSetting('new_tab')) {
        $options['attributes']['target'] = '_blank';
      }
      if ($this->getSetting('force_download')) {
        $options['attributes']['download'] = TRUE;
      }

      // Make the render array.
      $elements[$delta] = [
        '#type' => 'link',
        '#title' => $this->getSetting('link_text'),
        '#url' => Url::fromUri(file_create_url($file->getFileUri())),
        '#options' => $options,
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];
    }

    return $elements;
  }

}
