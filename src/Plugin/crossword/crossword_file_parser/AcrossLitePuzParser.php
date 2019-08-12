<?php

namespace Drupal\crossword\Plugin\crossword\crossword_file_parser;

use Drupal\Core\Plugin\PluginBase;
use Drupal\crossword\CrosswordFileParserPluginInterface;
use Drupal\file\Entity\File;

/**
 * @CrosswordFileParser(
 *   id = "across_lite_puz",
 *   title = @Translation("Across Lite Puz")
 * )
 */
class AcrossLitePuzParser extends PluginBase implements CrosswordFileParserPluginInterface {

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
  public function __construct($configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->file = File::load($configuration['fid']);
    if (!static::isApplicable($this->file)) {
      throw new \Exception('Chosen crossword file parser cannot parse this file.');
    }
    $this->contents = file_get_contents($this->file->getFileUri());
    $this->contents = trim($this->contents);
  }

  /**
   * {@inheritdoc}
   *
   * Checks for missing tags, extra tags, oout of order tags.
   */
  public static function isApplicable($file) {

    if ($file->getMimeType() !== 'application/octet-stream') {
      return FALSE;
    }

    if (strpos($file->getFilename(), ".puz") === FALSE) {
      return FALSE;
    }

    $contents = file_get_contents($file->getFileUri());
    $contents = trim($contents);
    if (substr($contents, 2, 11) !== "ACROSS&DOWN") {
      return FALSE;
    }

    return TRUE;

  }

  public function parse() {

    $hex = bin2hex($this->contents);
    $hex_arr = [];
    for($i = 0; $i < strlen($hex); $i = $i + 2){
      $hex_arr[] = substr($hex, $i, 2);
    }

    // Get dimensions. These are hex versions of numbers, not hex versinos of ASCII characters.
    $cols = hexdec($hex_arr[44]);
    $rows = hexdec($hex_arr[45]);
    $num_clues = hexdec($hex_arr[46]);

    // Starting with element 52, everything (almost) is text
    $dec_array = [];
    for ($i = 52; $i < count($hex_arr); $i++) {
      try {
        $dec = hexdec($hex_arr[$i]);
        $dec_array[] = $dec;
      } catch(Exception $e) {
        continue;
      }
    }

    // Concatonate the chars into meaningful lines
    // A line break is indicated by 0.

    $lines = [];
    $line = '';
    foreach ($dec_array as $i => $dec) {
      if ($dec == 0) {
        $lines[] = $line;
        $line = '';
      }
      else {
        try {
          $char = chr($dec);
          $line .= $char;
        } catch(Exception $e) {
          continue;
        }
      }
    }
    $lines[] = $line; // There's an un-added line at this point.

    $pre_parse = [
     'rows' => $rows,
     'cols' => $cols,
     'num_clues' => $num_clues,
     'lines' => $lines,
    ];

    $data = [
      'title' => $this->getTitle($pre_parse),
      'author' => $this->getAuthor($pre_parse),
      'notepad' => $this->getNotepad($pre_parse),
      'puzzle' => $this->getGridAndClues($pre_parse),
    ];

    \Drupal::moduleHandler()->alter('crossword_parse', $data, $this->contents);

    return $data;
  }

  public function getTitle($pre_parse) {
    // The first line has the solution grid, the saved answer grid, and then the title.
    return substr($pre_parse['lines'][0], 2 * $pre_parse['rows'] * $pre_parse['cols']);
  }

  public function getAuthor($pre_parse) {
    // It's the second line.
    return $pre_parse['lines'][1];
  }

  public function getNotepad($pre_parse) {
    // The clues start at line index 3.
    // The notepad comes right after the last clue.
    return $pre_parse['lines'][3 + $pre_parse['num_clues']];
  }

  public function getGridAndClues($pre_parse) {
    $grid = [];
    $clues = [
      'across' => [],
      'down' => [],
    ];

    $raw_clues = $this->getRawClues($pre_parse);
    $raw_grid = $this->getRawGrid($pre_parse);

    $iterator = [
      'index_across' => -1,
      'index_down' => -1,
      'index_raw_clue' => -1,
      'numeral' => 0,
    ];

    $rebus_array = $this->getRebusArray();

    foreach ($raw_grid as $row_index => $raw_row) {
      $row = [];
      for ($col_index = 0; $col_index < count($raw_row); $col_index++) {
        $fill = $raw_row[$col_index];
        $square = [
          'row' => $row_index,
          'col' => $col_index,
        ];
        if ($fill === NULL) {
          $square['fill'] = NULL;
        }
        else {
          // init some things to NULL
          $numeral_incremented = FALSE;
          $numeral = NULL;
          /**
           * This will be the first square in an across clue if...
           * 1. It's the left square or to the right of a black
           * AND
           * 2. It's not the right square and the square to its right is not black.
           */
          if ($col_index == 0 || $raw_row[$col_index - 1] === NULL) {
            if (isset($raw_row[$col_index + 1]) && $raw_row[$col_index + 1] !== NULL) {
              $iterator['index_across']++;
              $iterator['numeral']++;
              $iterator['index_raw_clue']++;
              $numeral = $iterator['numeral'];
              $clues['across'][] = [
                'text' => $raw_clues[$iterator['index_raw_clue']],
                'numeral' => $iterator['numeral'],
                'references' => $this->findReferences($raw_clues[$iterator['index_raw_clue']]),
              ];
              $numeral_incremented = TRUE;

              $square['fill'] = $fill;
              $square['across'] = [
                'index' => $iterator['index_across'],
              ];
              $square['numeral'] = $numeral;
            }
            else {
              // In here? It's an uncrosswed square. No across clue. No numeral.
              $square['fill'] = $fill;
            }
          }
          else {
            // In here? No numeral.
            $square['fill'] = $fill;
            $square['across'] = [
              'index' => $iterator['index_across'],
            ];
          }

          /**
           * This will be the first square in a down clue if...
           * 1. It's the top square or the below a black
           * AND
           * 2. It's not the bottom square and the square below it is not black.
           */
          if ($row_index == 0 || $raw_grid[$row_index - 1][$col_index] === NULL) {
            if (isset($raw_grid[$row_index + 1][$col_index]) && $raw_grid[$row_index + 1][$col_index] !== NULL) {
              $iterator['index_down']++;
              $iterator['index_raw_clue']++;
              if (!$numeral_incremented) {
                $iterator['numeral']++;
              }
              $numeral = $iterator['numeral'];
              $clues['down'][] = [
                'text' => $raw_clues[$iterator['index_raw_clue']],
                'numeral' => $iterator['numeral'],
                'references' => $this->findReferences($raw_clues[$iterator['index_raw_clue']]),
              ];
              $numeral_incremented = TRUE;

              $square['fill'] = $fill;
              $square['down'] = [
                'index' => $iterator['index_down'],
              ];
              $square['numeral'] = $numeral;
            }
            else {
              // In here? It's an uncrosswed square. No down clue. No numeral.
              $square['fill'] = $fill;
            }
          }
          else {
            // In here? No numeral. Take the down value from the square above.
            $square['fill'] = $fill;
            $square['down'] = $grid[$row_index - 1][$col_index]['down'];
          }
        }

        //is it a rebus square?
        if (is_numeric($square['fill']) && !empty($rebus_array) && isset($rebus_array[$square['fill'] - 1])) {
          $square['fill'] = $rebus_array[$square['fill'] - 1];
          $square['rebus'] = TRUE;
        }

        $row[] = $square;
      }
      $grid[] = $row;
    }

    $this->addIndexToClueReferences($clues);
    $this->addSquareMoves($grid, $clues);

    return [
      'grid' => $grid,
      'clues' => $clues,
    ];
  }

  private function addSquareMoves(&$grid) {
    foreach ($grid as $row_index => $row) {
      foreach ($row as $col_index => $square) {
        $grid[$row_index][$col_index]['moves'] = [
          'up' => NULL,
          'down' => NULL,
          'left' => NULL,
          'right' => NULL,
        ];
        //up
        if (isset($grid[$row_index - 1][$col_index]['fill'])) {
          $grid[$row_index][$col_index]['moves']['up'] = [
            'row' => $row_index - 1,
            'col' => $col_index,
          ];
        }
        //down
        if (isset($grid[$row_index + 1][$col_index]['fill'])) {
          $grid[$row_index][$col_index]['moves']['down'] = [
            'row' => $row_index + 1,
            'col' => $col_index,
          ];
        }
        //left
        if (isset($grid[$row_index][$col_index - 1]['fill'])) {
          $grid[$row_index][$col_index]['moves']['left'] = [
            'row' => $row_index,
            'col' => $col_index - 1,
          ];
        }
        //right
        if (isset($grid[$row_index][$col_index + 1]['fill'])) {
          $grid[$row_index][$col_index]['moves']['right'] = [
            'row' => $row_index,
            'col' => $col_index + 1,
          ];
        }
      }
    }
  }

  private function addIndexToClueReferences(&$clues) {
    foreach ($clues['down'] as &$down_clue) {
      if (!empty($down_clue['references'])) {
        foreach ($down_clue['references'] as &$reference) {
          foreach($clues[$reference['dir']] as $index => $clue) {
            if ($clue['numeral'] == $reference['numeral']) {
              $reference['index'] = $index;
            }
          }
        }
      }
    }
    foreach ($clues['across'] as &$across_clue) {
      if (!empty($across_clue['references'])) {
        foreach ($across_clue['references'] as &$reference) {
          foreach($clues[$reference['dir']] as $index => $clue) {
            if ($clue['numeral'] == $reference['numeral']) {
              $reference['index'] = $index;
            }
          }
        }
      }
    }
  }

  public function getRawClues($pre_parse) {
    // Clues start at index 3.
    return array_slice($pre_parse['lines'], 3, $pre_parse['num_clues'] + 1);
  }

  public function getRawGrid($pre_parse) {
    $grid_string = substr($pre_parse['lines'][0], 0, $pre_parse['rows'] * $pre_parse['cols']);
    $grid = [];
    $i = 0;
    for ($row_index = 0; $row_index < $pre_parse['rows']; $row_index++) {
      $row = [];
      for ($col_index = 0; $col_index < $pre_parse['cols']; $col_index++) {
        $row[] = ($grid_string[$i] == ".") ? NULL : $grid_string[$i];
        $i++;
      }
      $grid[] = $row;
    }

    return $grid;
  }

  private function findReferences($text) {
    //find references
    $refRegex = '/(\d+\-)|(Down)|(Across)/';
    if( preg_match('/(\d+\-)/', $text) === 1 && preg_match('/(Across)|(Down)/', $text) === 1 ){
      //there's likely a reference
      $matches = [];
      $references = [];
      preg_match_all($refRegex, $text, $matches);
      $matches = $matches[0]; //something like [13- , 23- , Across, 45-, Down]
      $across_index = array_search("Across", $matches);
      $down_index = array_search("Down", $matches);

      if( $across_index === FALSE ){
        //just down references
        $i = 0;
        while( $i < $down_index ){
          $ref_num = str_replace("-", "", $matches[$i]);
          $references[] = [
            'dir' => 'down',
            'numeral' => $ref_num,
          ];
          $i++;
        }
      }
      if( $down_index === FALSE ){
        //just across references
        $i = 0;
        while( $i < $across_index ){
          $ref_num = str_replace("-", "", $matches[$i]);
          $references[] = [
            'dir' => 'across',
            'numeral' => $ref_num,
          ];
          $i++;
        }
      }
      if( $across_index > -1 && $down_index > -1 ){
        //assume Across references are first, as they should be
        //across
        $i = 0;
        while( $i < $across_index ){
          $ref_num = str_replace("-", "", $matches[$i]);
          $references[] = [
            'dir' => 'across',
            'numeral' => $ref_num,
          ];
          $i++;
        }
        //now down. We have to move past the acrossIndex
        $i = $across_index + 1;
        while( $i < $down_index ){
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

  private function getRebusArray() {
    return NULL;
    if (array_search("<REBUS>", $this->lines) > -1) {
      $rebus_start_index = array_search("<REBUS>", $this->lines) + 1;
      $rebus_array = [];
      $i = $rebus_start_index;
      while ($this->lines[$i] != "<ACROSS>") {
        $line = explode(':', $this->lines[$i]);
        if (isset($line[1])) {
          $rebus_array[] = $line[1];
        }
        $i++;
      }
      return $rebus_array;
    }
  }

}
