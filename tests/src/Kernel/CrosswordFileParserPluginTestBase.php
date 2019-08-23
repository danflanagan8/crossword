<?php

namespace Drupal\Tests\crossword\Kernel;

use Drupal\KernelTests\KernelTestBase;

abstract class CrosswordFileParserPluginTestBase extends KernelTestBase {

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
   *  @var string
   */
  public $plugin_id;

  /**
   *  Class of the plugin.
   *
   *  @var string
   */
  public $class;

  /**
   *  Filenames used for tests. Should look something like...
   *  array(
   *   'success' => 'test.ext',
   *   'failure' => 'not-a-puzzle.ext',
   *  )
   *
   *  @var array
   */
  public $filename;

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

  /**
   * Using a file we expect is not applicable, test that the parser fails.
   */
  public function testParserFailure() {
    $file = $this->getTestFile($this->filename['failure']);

    // Check that the parser is not applicable
    $applicable = $this->class::isApplicable($file);
    $this->assertFalse($applicable);

    $failed = FALSE;
    try {
      $parser = $this->parserManager->createInstance($this->plugin_id, ['fid' => $file->id()]);
    }
    catch (\Exception $e) {
      $failed = TRUE;
    }
    $this->assertTrue($failed);

  }

  /**
   *  We test that an applicable file returns an expected array.
   */
  public function testParserSuccess() {
    $file = $this->getTestFile($this->filename['success']);

    // Check that the parser is applicable
    $applicable = $this->class::isApplicable($file);
    $this->assertTrue($applicable == TRUE, $applicable);

    // This is the expected json for the test file.
    $expected_json = $this->getTestJSON();
    // Turn it into an expected array
    $expected_data = json_decode($expected_json, TRUE);
    $expected_data['id'] = $file->id();

    // Get the real output of the parser
    $parser = $this->parserManager->createInstance($this->plugin_id, ['fid' => $file->id()]);
    $data = $parser->parse();

    $this->assertTrue($data == $expected_data, json_encode($data));

  }

  public function getTestFile($filename) {
    $contents = file_get_contents(drupal_get_path('module', 'crossword') . "/tests/files/" . $filename);
    $file = file_save_data($contents, "public://$filename");
    return $file;
  }

  /**
   * Loads the expected json for the success file.
   */
  public function getTestJSON() {
    $json = file_get_contents(drupal_get_path('module', 'crossword') . "/tests/files/test.json");
    return $json;
  }
}
