<?php

namespace Drupal\Tests\crossword\Kernel;

/**
 * Tests the Across Lite Puz parser plugin
 *
 * @group crossword
 */
class AcrossLitePuzParserTest extends CrosswordFileParserPluginTestBase {

  public $plugin_id = 'across_lite_puz';
  public $class = 'Drupal\crossword\Plugin\crossword\crossword_file_parser\AcrossLitePuzParser';
  public $filename = 'test.puz';

}
