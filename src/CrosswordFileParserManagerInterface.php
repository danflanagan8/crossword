<?php

namespace Drupal\crossword;

use Drupal\file\FileInterface;

interface CrosswordFileParserManagerInterface {


  /**
   * Get the provider applicable to the given user input.
   *
   * @param array $definitions
   *   A list of definitions to test against.
   * @param \Drupal\file\FileInterface $file
   *   The user input file to test against the plugins.
   *
   * @return \Drupal\crossword\CrosswordFileParserPluginInterface|bool
   *   The relevant plugin or FALSE on failure.
   */
  public function filterApplicableDefinitions(array $definitions, FileInterface $file);


  /**
   * Load a crossword file parser from user input file.
   *
   * @param \Drupal\file\FileInterface $file
   *   File provided from a field.
   *
   * @return \Drupal\crossword\CrosswordFileParserPluginInterface|bool
   *   The loaded plugin.
   */
  public function loadCrosswordFileParserFromInput(FileInterface $file);

  /**
   * Load a plugin definition from an input.
   *
   * @param \Drupal\file\FileInterface $file
   *   An input string.
   *
   * @return array
   *   A plugin definition.
   */
  public function loadDefinitionFromInput(FileInterface $file);

}
