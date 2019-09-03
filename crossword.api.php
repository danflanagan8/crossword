<?php

/**
 * @file
 * Hooks provided by the crossword module.
 */

use Drupal\file\FileInterface;

/**
 * Alter the array representing the crossword.
 *
 * See \Drupal\crossword\CrosswordFileParserPluginBase::parse().
 *
 * @param array $data
 *   The parsed crossword file, ready to be used by formatters and/or
 *   passed to drupalSettings.
 * @param Drupal\file\FileInterface $file
 *   The crossword file.
 */
function hook_crossword_data_alter(array &$data, FileInterface $file) {
  // Take credit for other people's work.
  $data['title'] = 'Dan';

  /*
   * A more interesting use would be to change the logic for finding the
   * the "moves" array. The default UX is that you get stopped by black
   * squares and edges when using the arrow keys to move the active square.
   * Perhaps you want to move through these barriers. This hook is one place
   * you could do that.
   */

}
