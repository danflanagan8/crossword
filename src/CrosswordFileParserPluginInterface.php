<?php

namespace Drupal\crossword;

use Drupal\file\FileInterface;

/**
 * Provides an interface for crossword file parser.
 */
interface CrosswordFileParserPluginInterface {

  /**
   * Determine whether the parser works for the input file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The user input file to test against the plugins.
   *
   * @return bool
   *   Returns TRUE if the plugin can parse the input file.
   */
  public static function isApplicable(FileInterface $file);

}
