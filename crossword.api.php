<?php

/**
 * @file
 * Hooks specific to the crossword module.
 */

/**
 * Alter the object representing the crossword file as returned by
 * the parse() method of the CrosswordFileParser class.
 *
 * @param array $data
 *   The parsed crossword file, ready to be used by formatters and/or
 *   passed to drupalSettings.
 *
 * @param string $contents
 *   The contents of the text file representing the crossword.
 */
function hook_crossword_data_alter(&$data, $contents) {
  // Take credit for other people's work
  $data['title'] = 'Dan';

  /*
   * A more interesting use would be to change the logic for finding the
   * the "moves" array. The default UX is that you get stopped by black
   * squares and edges when using the arrow keys to move the active square.
   * Perhaps you want to move through these barriers. This hook is one place
   * you could do that.
   */

}
