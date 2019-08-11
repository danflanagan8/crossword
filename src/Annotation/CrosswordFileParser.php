<?php

namespace Drupal\crossword\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CrosswordFileParser item annotation object.
 *
 * @Annotation
 */
class CrosswordFileParser extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

}
