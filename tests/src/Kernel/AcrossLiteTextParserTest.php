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

  public function testParser() {
    // Load a text file and create an entity
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('crossword')->getPath();
    $contents = file_get_contents("http://crossword-commons.dd:8083/" . $module_path . "/tests/files/test.txt");
    $file = file_save_data($contents, 'public://test_text.txt');

    // Check that the parser is applicable
    $applicable = \Drupal\crossword\Plugin\crossword\crossword_file_parser\AcrossLiteTextParser::isApplicable($file);
    $this->assertTrue($applicable == TRUE, $applicable);

    // This is the expected json for the test file.
    $expected_json = '{"id":"1","title":"Test Puzzle","author":"Test Author","notepad":"This is a test.","puzzle":{"grid":[[{"row":0,"col":0,"circle":false,"rebus":false,"fill":"A","down":{"index":0},"numeral":1,"moves":{"up":null,"down":{"row":1,"col":0},"left":null,"right":null}},{"row":0,"col":1,"circle":false,"rebus":false,"fill":null,"moves":{"up":null,"down":{"row":1,"col":1},"left":{"row":0,"col":0},"right":{"row":0,"col":2}}},{"row":0,"col":2,"circle":false,"rebus":true,"fill":"ONE","down":{"index":1},"numeral":2,"moves":{"up":null,"down":{"row":1,"col":2},"left":null,"right":null}}],[{"row":1,"col":0,"circle":false,"rebus":false,"fill":"B","across":{"index":0},"numeral":3,"down":{"index":0},"moves":{"up":{"row":0,"col":0},"down":{"row":2,"col":0},"left":null,"right":{"row":1,"col":1}}},{"row":1,"col":1,"circle":true,"rebus":false,"fill":"c","across":{"index":0},"down":{"index":2},"numeral":4,"moves":{"up":null,"down":{"row":2,"col":1},"left":{"row":1,"col":0},"right":{"row":1,"col":2}}},{"row":1,"col":2,"circle":false,"rebus":false,"fill":"D","across":{"index":0},"down":{"index":1},"moves":{"up":{"row":0,"col":2},"down":{"row":2,"col":2},"left":{"row":1,"col":1},"right":null}}],[{"row":2,"col":0,"circle":false,"rebus":true,"fill":"TWO","across":{"index":1},"numeral":5,"down":{"index":0},"moves":{"up":{"row":1,"col":0},"down":null,"left":null,"right":{"row":2,"col":1}}},{"row":2,"col":1,"circle":false,"rebus":false,"fill":"E","across":{"index":1},"down":{"index":2},"moves":{"up":{"row":1,"col":1},"down":null,"left":{"row":2,"col":0},"right":{"row":2,"col":2}}},{"row":2,"col":2,"circle":false,"rebus":false,"fill":"F","across":{"index":1},"down":{"index":1},"moves":{"up":{"row":1,"col":2},"down":null,"left":{"row":2,"col":1},"right":null}}]],"clues":{"across":[{"text":"3A Second square has a circle","numeral":3,"references":null},{"text":"5A Has a reference to 3-Across and 1-Down","numeral":5,"references":[{"dir":"across","numeral":"3","index":0},{"dir":"down","numeral":"1","index":0}]}],"down":[{"text":"1D is AB2","numeral":1,"references":null},{"text":"2D is 1DF","numeral":2,"references":null},{"text":"4D Starts with a circle","numeral":4,"references":null}]}}}';
    // Turn it into an expected array
    $expected_data = json_decode($expected_json, TRUE);

    // Get the real output of the parser
    $parser = $this->parserManager->createInstance('across_lite_text', ['fid' => $file->id()]);
    $data = $parser->parse();

    $this->assertTrue($data == $expected_data, json_encode($data));

  }
}
