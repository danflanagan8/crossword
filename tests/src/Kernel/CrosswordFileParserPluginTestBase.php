<?php

namespace Drupal\Tests\crossword\Kernel;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\KernelTests\KernelTestBase;
use \Drupal\Core\File\FileSystemInterface;

abstract class CrosswordFileParserPluginTestBase extends KernelTestBase {

  /**
   *
   * @var \Drupal\crossword\CrosswordFileParserManagerInterface
   */
  protected $parserManager;

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;


  /**
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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
   *  Filename of file to parse. Should be located in crossword/tests/files
   *
   *  @var string
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
    $this->fileStorage = \Drupal::service('entity_type.manager')->getStorage('file');
    $this->fileSystem = \Drupal::service('file_system');
  }

  public function testParser() {
    // Load a text file and create an entity
    // $module_handler = \Drupal::service('module_handler');
    // $module_path = $module_handler->getModule('crossword')->getPath();
    // $base_root = $GLOBALS['base_root'];
    // $contents = file_get_contents("/" . $module_path . "/tests/files/" . $this->filename);
    // $file = file_save_data($contents, "public://{$this->filename}");
    $file = $this->getTestFile();
    $file->save();

    // Check that the parser is applicable
    $applicable = $this->class::isApplicable($file);
    $this->assertTrue($applicable == TRUE, $applicable);

    // This is the expected json for the test file.
    $expected_json = '{"id":"1","title":"Test Puzzle","author":"Test Author","notepad":"This is a test.","puzzle":{"grid":[[{"row":0,"col":0,"circle":false,"rebus":false,"fill":"A","down":{"index":0},"numeral":1,"moves":{"up":null,"down":{"row":1,"col":0},"left":null,"right":null}},{"row":0,"col":1,"circle":false,"rebus":false,"fill":null,"moves":{"up":null,"down":{"row":1,"col":1},"left":{"row":0,"col":0},"right":{"row":0,"col":2}}},{"row":0,"col":2,"circle":false,"rebus":true,"fill":"ONE","down":{"index":1},"numeral":2,"moves":{"up":null,"down":{"row":1,"col":2},"left":null,"right":null}}],[{"row":1,"col":0,"circle":false,"rebus":false,"fill":"B","across":{"index":0},"numeral":3,"down":{"index":0},"moves":{"up":{"row":0,"col":0},"down":{"row":2,"col":0},"left":null,"right":{"row":1,"col":1}}},{"row":1,"col":1,"circle":true,"rebus":false,"fill":"C","across":{"index":0},"down":{"index":2},"numeral":4,"moves":{"up":null,"down":{"row":2,"col":1},"left":{"row":1,"col":0},"right":{"row":1,"col":2}}},{"row":1,"col":2,"circle":false,"rebus":false,"fill":"D","across":{"index":0},"down":{"index":1},"moves":{"up":{"row":0,"col":2},"down":{"row":2,"col":2},"left":{"row":1,"col":1},"right":null}}],[{"row":2,"col":0,"circle":false,"rebus":true,"fill":"TWO","across":{"index":1},"numeral":5,"down":{"index":0},"moves":{"up":{"row":1,"col":0},"down":null,"left":null,"right":{"row":2,"col":1}}},{"row":2,"col":1,"circle":false,"rebus":false,"fill":"E","across":{"index":1},"down":{"index":2},"moves":{"up":{"row":1,"col":1},"down":null,"left":{"row":2,"col":0},"right":{"row":2,"col":2}}},{"row":2,"col":2,"circle":false,"rebus":false,"fill":"F","across":{"index":1},"down":{"index":1},"moves":{"up":{"row":1,"col":2},"down":null,"left":{"row":2,"col":1},"right":null}}]],"clues":{"across":[{"text":"3A Second square has a circle","numeral":3,"references":null},{"text":"5A Has a reference to 3-Across and 1-Down","numeral":5,"references":[{"dir":"across","numeral":"3","index":0},{"dir":"down","numeral":"1","index":0}]}],"down":[{"text":"1D is AB2","numeral":1,"references":null},{"text":"2D is 1DF","numeral":2,"references":null},{"text":"4D Starts with a circle","numeral":4,"references":null}]}}}';

    // Turn it into an expected array
    $expected_data = json_decode($expected_json, TRUE);
    $expected_data['id'] = $file->id();

    // Get the real output of the parser
    $parser = $this->parserManager->createInstance($this->plugin_id, ['fid' => $file->id()]);
    $data = $parser->parse();

    $this->assertTrue($data == $expected_data, json_encode($data));

  }

  public function getTestFile() {
    $this->fileSystem->copy(drupal_get_path('module', 'crossword') . "/tests/files/{$this->filename}", PublicStream::basePath());
    return $this->fileStorage->create([
      'uri' => "public://{$this->filename}",
      'status' => FILE_STATUS_PERMANENT,
    ]);
  }
}
