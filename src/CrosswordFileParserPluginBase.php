<?php

namespace Drupal\crossword;

use Drupal\Core\Plugin\PluginBase;
use Drupal\file\Entity\File;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\FileInterface;

/**
 * If there's a crossword file format that no existing Crossword File Parser
 * Plugin can figure out, extend this class to write your own.
 */
abstract class CrosswordFileParserPluginBase extends PluginBase implements CrosswordFileParserPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Cache for the result of the parse function.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;


  /**
   * The file entity that hopefully represents a crossword.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * The contents of the file.
   *
   * @var string
   */
  protected $contents;

  /**
   * Create a plugin with the given input.
   *
   * @param string $configuration
   *   The configuration of the plugin.
   * @param string $plugin_id
   *   The plugin id.
   * @param array $plugin_definition
   *   The plugin definition.
   *
   * @throws \Exception
   */
  public function __construct($configuration, $plugin_id, $plugin_definition, CacheBackendInterface $cache) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->file = File::load($configuration['fid']);
    if (!static::isApplicable($this->file)) {
      throw new \Exception('Chosen crossword file parser cannot parse this file.');
    }
    $this->cache = $cache;
    $this->contents = file_get_contents($this->file->getFileUri());
    $this->contents = trim($this->contents);
  }

  /**
   *
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('cache.crossword')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FileInterface $file) {
    return FALSE;
  }

  /**
   * Plugins that extend this base should have their own getData() function.
   * The parse function is final so that caching and the data alter hook
   * are standardized.
   */
  final public function parse() {

    $cached = $this->cache->get($this->file->id());
    if (isset($cached->data['data'])) {
      $data = $cached->data['data'];
    }
    else {
      $data = $this->getData();
      $this->cache->set($this->file->id(), ["data" => $data], CacheBackendInterface::CACHE_PERMANENT, $this->file->getCacheTags());
    }

    \Drupal::moduleHandler()->alter('crossword_data', $data, $this->contents);

    return $data;
  }

  /**
   * The "data" array is what the field formatter ends up using. If you extend
   * this base class, you definitely need to override this function.
   *
   * Array(
   *   'id' => $this->file->id(),
   *   'title' => 'Awesome Puzzle',
   *   'author' => 'Dan',
   *   'notepad' => 'These are notes included in the file',
   *   'puzzle' => [
   *     'grid' => array of squares
   *     'clues' => [
   *       'across' => array of clues
   *       'down' => array of clues
   *     ],
   *   ],
   * )
   *
   * A square looks like this...
   * array(
   *  'fill' => NULL or a string,
   *  'numeral' => NULL or a number,
   *  'across' => [
   *    'index' => index of across clue
   *  ],
   *  'down' => [
   *    'index' => index of down clue
   *  ],
   *  'moves' => [
   *    'up' => ['row': number, 'col': number] or NULL
   *    'down' => ['row': number, 'col': number] or NULL
   *    'left' => ['row': number, 'col': number] or NULL
   *    'right' => ['row': number, 'col': number] or NULL
   *  ],
   *  'circle' => bool,
   *  'rebus' => bool,
   * )
   *
   * A clue looks like this...
   * array(
   *  'text' => string,
   *  'numeral' => number,
   *  'references' => array(
   *    [
   *      'dir' => 'down' or 'across',
   *      'numeral' => number,
   *      'index' => number,
   *    ],
   *   )
   * )
   */
  protected function getData() {
    return [
      'id' => $this->file->id(),
      'title' => NULL,
      'author' => NULL,
      'notepad' => NULL,
      'puzzle' => NULL,
    ];
  }

  /**
   * If the text of a clue is something like "Common feature of 12- and 57-Across and
   * 34-Down", the return value will be.
   *
   * Array(
   *  [
   *   'dir' => 'across',
   *   'numeral' => 12,
   *  ],
   *  [
   *   'dir' => 'across',
   *   'numeral' => 57,
   *  ],
   *  [
   *   'dir' => 'down',
   *   'numeral' => 34,
   *  ],
   * )
   */
  protected function findReferences($text) {
    // Find references.
    $refRegex = '/(\d+\-)|(Down)|(Across)/';
    if (preg_match('/(\d+\-)/', $text) === 1 && preg_match('/(Across)|(Down)/', $text) === 1) {
      // there's likely a reference.
      $matches = [];
      $references = [];
      preg_match_all($refRegex, $text, $matches);
      // Something like [13- , 23- , Across, 45-, Down].
      $matches = $matches[0];
      $across_index = array_search("Across", $matches);
      $down_index = array_search("Down", $matches);

      if ($across_index === FALSE) {
        // Just down references.
        $i = 0;
        while ($i < $down_index) {
          $ref_num = str_replace("-", "", $matches[$i]);
          $references[] = [
            'dir' => 'down',
            'numeral' => $ref_num,
          ];
          $i++;
        }
      }
      if ($down_index === FALSE) {
        // Just across references.
        $i = 0;
        while ($i < $across_index) {
          $ref_num = str_replace("-", "", $matches[$i]);
          $references[] = [
            'dir' => 'across',
            'numeral' => $ref_num,
          ];
          $i++;
        }
      }
      if ($across_index > -1 && $down_index > -1) {
        // Assume Across references are first, as they should be
        // across.
        $i = 0;
        while ($i < $across_index) {
          $ref_num = str_replace("-", "", $matches[$i]);
          $references[] = [
            'dir' => 'across',
            'numeral' => $ref_num,
          ];
          $i++;
        }
        // Now down. We have to move past the acrossIndex.
        $i = $across_index + 1;
        while ($i < $down_index) {
          $ref_num = str_replace("-", "", $matches[$i]);
          $references[] = [
            'dir' => 'down',
            'numeral' => $ref_num,
          ];
          $i++;
        }
      }
      return $references;
    }
  }

  /**
   * $clues is the 'clues' element of $data, as described above the detData
   *  function. The clues should be fully created other than the index
   *  element of any references.
   */
  protected function addIndexToClueReferences(&$clues) {
    foreach ($clues['down'] as &$down_clue) {
      if (!empty($down_clue['references'])) {
        foreach ($down_clue['references'] as &$reference) {
          foreach ($clues[$reference['dir']] as $index => $clue) {
            if ($clue['numeral'] == $reference['numeral']) {
              $reference['index'] = $index;
              break;
            }
          }
        }
      }
    }
    foreach ($clues['across'] as &$across_clue) {
      if (!empty($across_clue['references'])) {
        foreach ($across_clue['references'] as &$reference) {
          foreach ($clues[$reference['dir']] as $index => $clue) {
            if ($clue['numeral'] == $reference['numeral']) {
              $reference['index'] = $index;
              break;
            }
          }
        }
      }
    }
  }

  /**
   * This tells the arrow keys what to do when the puzzle is rendered.
   * By default, arrow keys won't move through black squares they get stopped
   * by the edges of the puzzle. If you want to modify this UX, the best
   * way may be to leverage hook_crossword_data_alter().
   */
  protected function addSquareMoves(&$grid) {
    foreach ($grid as $row_index => $row) {
      foreach ($row as $col_index => $square) {
        $grid[$row_index][$col_index]['moves'] = [
          'up' => NULL,
          'down' => NULL,
          'left' => NULL,
          'right' => NULL,
        ];
        // Up.
        if (isset($grid[$row_index - 1][$col_index]['fill'])) {
          $grid[$row_index][$col_index]['moves']['up'] = [
            'row' => $row_index - 1,
            'col' => $col_index,
          ];
        }
        // Down.
        if (isset($grid[$row_index + 1][$col_index]['fill'])) {
          $grid[$row_index][$col_index]['moves']['down'] = [
            'row' => $row_index + 1,
            'col' => $col_index,
          ];
        }
        // Left.
        if (isset($grid[$row_index][$col_index - 1]['fill'])) {
          $grid[$row_index][$col_index]['moves']['left'] = [
            'row' => $row_index,
            'col' => $col_index - 1,
          ];
        }
        // Right.
        if (isset($grid[$row_index][$col_index + 1]['fill'])) {
          $grid[$row_index][$col_index]['moves']['right'] = [
            'row' => $row_index,
            'col' => $col_index + 1,
          ];
        }
      }
    }
  }

}
