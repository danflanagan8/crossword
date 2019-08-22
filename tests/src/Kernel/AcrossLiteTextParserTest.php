<?php

namespace Drupal\Tests\crossword\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the MyService
 *
 * @group crossword
 */
class AcrossLiteTextParserTest extends KernelTestBase {

  /**
   *
   * @var \Drupal\crossword\CrosswordFileParserManagerInterface
   */
  protected $parserManager;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  public static $modules = ['system','crossword','file','user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['file', 'user']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');

    $this->parserManager = \Drupal::service('crossword.manager.parser');
  }

  public function testApplicable() {
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('crossword')->getPath();
    $contents = file_get_contents("http://crossword-commons.dd:8083/" . $module_path . "/tests/files/first_betsie.txt");
    $file = file_save_data($contents, 'public://test_text.txt');
    $applicable = \Drupal\crossword\Plugin\crossword\crossword_file_parser\AcrossLiteTextParser::isApplicable($file);
    $this->assertTrue($applicable == TRUE, $applicable);
  }
}
