<?php

namespace Drupal\crossword;

/**
 * Providers an interface for crossword file parser.
 */
interface CrosswordFileParserPluginInterface {

  public static function isApplicable($file);

}
