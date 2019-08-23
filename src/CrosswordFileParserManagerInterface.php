<?php

namespace Drupal\crossword;

use Drupal\file\FileInterface;

/**
 * Interface for Crossword File Parser Manager.
 */
interface CrosswordFileParserManagerInterface {

  /**
   * Get a parser applicable to the given user input.
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

  /**
   * Get an options list suitable for form elements for parser selection.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getInstalledParsersOptionList();

  /**
   * Load the parser plugin definitions from a FAPI options list value.
   *
   * @param array $options
   *   An array of options from a form API submission.
   *
   * @return array
   *   An array of plugin definitions.
   */
  public function loadDefinitionsFromOptionList(array $options);

}
