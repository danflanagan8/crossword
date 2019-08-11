<?php

namespace Drupal\crossword;

interface CrosswordFileParserManagerInterface {

  public function filterApplicableDefinitions(array $definitions, $file);

  public function loadCrosswordFileParserFromInput($file);

  public function loadDefinitionFromInput($file);

}
