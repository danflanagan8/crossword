<?php

namespace Drupal\Tests\crossword\Kernel;

/**
 * Tests the Across Lite Text parser plugin.
 *
 * @group crossword
 */
class AcrossLiteTextParserTest extends CrosswordFileParserPluginTestBase {

  public $pluginId = 'across_lite_text';
  public $class = 'Drupal\crossword\Plugin\crossword\crossword_file_parser\AcrossLiteTextParser';
  public $filename = [
    'success' => 'test.txt',
    'failure' => 'failure.txt',
  ];

}
