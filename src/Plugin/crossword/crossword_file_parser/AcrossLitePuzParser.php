<?php

namespace Drupal\crossword\Plugin\crossword\crossword_file_parser;

use Drupal\Core\Plugin\PluginBase;
use Drupal\crossword\CrosswordFileParserPluginInterface;
use Drupal\file\Entity\File;
use Drupal\crossword\CrosswordFileParserBase;

/**
 * @CrosswordFileParser(
 *   id = "across_lite_puz",
 *   title = @Translation("Across Lite Puz")
 * )
 */
class AcrossLitePuzParser extends CrosswordFileParserBase {


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

  protected function getData() {

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
    $dec_lines = [];
    $line = '';
    $dec_line = '';
    foreach ($dec_array as $i => $dec) {
      if ($dec == 0) {
        $dec_lines[] = $dec_line;
        $dec_line = '';
        $lines[] = $line;
        $line = '';
      }
      else {
        $dec_line .= $dec;
        try {
          $char = chr($dec);
          $line .= $char;
        } catch(Exception $e) {
          continue;
        }
      }
    }
    $dec_lines[] = $dec_line;
    $lines[] = $line; // There's an un-added line at this point.

    $pre_parse = [
     'rows' => $rows,
     'cols' => $cols,
     'num_clues' => $num_clues,
     'lines' => $lines,
     'dec_lines' => $dec_lines,
    ];

    $data = [
      'id' => $this->file->id(), 
      'title' => $this->getTitle($pre_parse),
      'author' => $this->getAuthor($pre_parse),
      'notepad' => $this->getNotepad($pre_parse),
      'puzzle' => $this->getGridAndClues($pre_parse),
    ];

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

    $rebus_grid = $this->getRebusGrid($pre_parse);

    foreach ($raw_grid as $row_index => $raw_row) {
      $row = [];
      for ($col_index = 0; $col_index < count($raw_row); $col_index++) {

        if (!empty($rebus_grid) && $rebus_grid[$row_index][$col_index] !== 0) {
          $fill = $rebus_grid[$row_index][$col_index];
        }
        else {
          $fill = $raw_row[$col_index];
        }
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

  // Returns a grid that has zeros or rebus entries.
  // This was really hard to write and may seem magical.
  private function getRebusGrid($pre_parse) {
    // If there's anything after the notepad, it's for the rebus.
    if (!isset($pre_parse['lines'][3 + $pre_parse['num_clues'] + 1])) {
      return [];
    }
    //the next line indicates the number of rebus squares, but we don't actually need it.
    // Now the next rows * cols lines are representative of squares.
    // 0 means the square is not a rebus
    // Something like 9 indicates that the square contains the letters that correspond to rebus #8.
    // I have no idea why the numbers are offset like that.
    // For these rebus grid lines, they are decimal values, not chars.
    $rebus_grid_lines = array_slice($pre_parse['dec_lines'], 3 + $pre_parse['num_clues'] + 3, $pre_parse['rows'] * $pre_parse['cols']);
    $i = 0;
    $rebus_grid_lines_processed = [];
    // We have to do this processing because the 0 following a rebus entry gets stipped in the pre_parse function.
    // Also, if it starts with a zero, a sero got stripped off the beginning.
    if ($rebus_grid_lines[0] == '') {
      array_unshift($rebus_grid_lines, '');
    }
    while (count($rebus_grid_lines_processed) < $pre_parse['rows'] * $pre_parse['cols']) {
      if (strlen($rebus_grid_lines[$i]) > 1) {
        // in here indicates multiple consecutive rebus entries
        $rebus_grid_lines_processed[] = $rebus_grid_lines[$i][0];
        $rebus_grid_lines[$i] = substr($rebus_grid_lines[$i], 1);
      }
      else if (strlen($rebus_grid_lines[$i]) == 1){
        // in here means a rebus entry not followed by a rebus.
        // The 0 after this got stripped in the pre_parse function.
        $rebus_grid_lines_processed[] = $rebus_grid_lines[$i];
        $rebus_grid_lines_processed[] = 0;
        $i++;
      }
      else {
        $rebus_grid_lines_processed[] = 0;
        $i++;
      }
    }

    $rebus_code_line = $pre_parse['lines'][count($pre_parse['lines']) - 1];
    // The first three chars are worthless
    $rebus_code_line = substr($rebus_code_line, 3);
    $rebus_code_line_array = explode(";", $rebus_code_line);
    foreach($rebus_code_line_array as &$val) {
      $val = trim(strtoupper($val)); //The module always wants rebus to be in caps.
    }
    $rebus_key_val_array = [];
    foreach($rebus_code_line_array as $val) {
      $exploded = explode(":", $val);
      $rebus_key_val_array[$exploded[0]] = $exploded[1];
    }

    foreach($rebus_grid_lines_processed as &$square) {
      if ($square != 0) {
        $square = $rebus_key_val_array[$square - 1];
      }
    }
    $rebus_grid_array = array_chunk($rebus_grid_lines_processed, $pre_parse['cols']);
    return $rebus_grid_array;

  }

}
