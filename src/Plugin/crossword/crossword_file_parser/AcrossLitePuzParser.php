<?php

namespace Drupal\crossword\Plugin\crossword\crossword_file_parser;

use Drupal\Core\Plugin\PluginBase;
use Drupal\file\Entity\File;
use Drupal\crossword\CrosswordFileParserPluginBase;

/**
 * @CrosswordFileParser(
 *   id = "across_lite_puz",
 *   title = @Translation("Across Lite Puz")
 * )
 */
class AcrossLitePuzParser extends CrosswordFileParserPluginBase {


  /**
   * {@inheritdoc}
   *
   * Checks for right mimetype and file extension.
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


  /**
   * {@inheritdoc}
   */
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
      'id' => $this->file->id(),
      'title' => $this->getTitle($pre_parse),
      'author' => $this->getAuthor($pre_parse),
      'notepad' => $this->getNotepad($pre_parse),
      'puzzle' => $this->getGridAndClues($pre_parse),
    ];

    $this->addIndexToClueReferences($data['puzzle']['clues']);
    $this->addSquareMoves($data['puzzle']['grid']);

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
    $circle_grid = $this->getCircleGrid($pre_parse);

    foreach ($raw_grid as $row_index => $raw_row) {
      $row = [];
      for ($col_index = 0; $col_index < count($raw_row); $col_index++) {

        if (!empty($rebus_grid) && $rebus_grid[$row_index][$col_index]) {
          $fill = $rebus_grid[$row_index][$col_index];
        }
        else {
          $fill = $raw_row[$col_index];
        }

        $circle = !empty($circle_grid) && $circle_grid[$row_index][$col_index];

        $square = [
          'row' => $row_index,
          'col' => $col_index,
          'circle' => $circle,
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

    return [
      'grid' => $grid,
      'clues' => $clues,
    ];
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


  // Returns a grid that has zeros or rebus entries.
  // This was really hard to write and may seem magical.
  private function getRebusGrid($pre_parse) {

    // search hex for 5254424c
    $hex_contents = bin2hex($this->contents);
    dpm($hex_contents);
    if (strpos($hex_contents, '5254424c') > -1) {
      $rebus_grid_end_index = strpos($hex_contents, '5254424c');
      // The previous 2 * $pre_parse['rows'] * $pre_parse['cols'] values represent squares.
      // 00 indicates no rebus. Anything else indicates rebus. 07 indicates rebus #6, for example.
      $rebus_grid_string = substr($hex_contents, $rebus_grid_end_index - 2 * $pre_parse['rows'] * $pre_parse['cols'] - 2, 2 * $pre_parse['rows'] * $pre_parse['cols']);
      $rebus_grid_lines = str_split($rebus_grid_string, 2 * $pre_parse['cols']);
      $rebus_grid = [];
      foreach($rebus_grid_lines as $line) {
        $row = str_split($line, 2);
        foreach ($row as &$hex){
          $hex = hexdec($hex);
        }
        $rebus_grid[] = $row;
      }

      // the rebus code starts at index $rebus_grid_end_index + 18 and goes until there's a 00.
      $rebus_code_string = '';
      $i = $rebus_grid_end_index + 18;
      while (isset($hex_contents[$i]) && substr($hex_contents, $i, 2) !== '00') {
        $dec = hexdec(substr($hex_contents, $i, 2));
        $char = chr($dec);
        $rebus_code_string .= $char;
        $i += 2;
      }
      $rebus_code_array = explode(";", $rebus_code_string);
      foreach ($rebus_code_array as &$elem) {
        $elem = trim($elem);
      }
      $rebus_key_val_array = [];
      foreach ($rebus_code_array as $val) {
        $exploded = explode(":", $val);
        $rebus_key_val_array[$exploded[0]] = $exploded[1];
      }

      foreach ($rebus_grid as $row_index => $rebus_row) {
        foreach ($rebus_row as $col_index => $rebus_square) {
          if ($rebus_square != 0) {
            $rebus_grid[$row_index][$col_index] = $rebus_key_val_array[$rebus_square - 1];
          }
          else {
            $rebus_grid[$row_index][$col_index] = 0;
          }
        }
      }

      return $rebus_grid;
    }
  }

  // Returns a grid that has zeros or not zeros
  // Not zeros means it's a circle
  protected function getCircleGrid($pre_parse) {
    $hex = bin2hex($this->contents);

    //we look for 47455854
    if (strpos($hex, '47455854') > -1) {
      $cricle_grid_start_index = strpos($hex, '47455854') + 16; //16 is the length of 47455854 plus 4 more hex "doublets"
      // The next 2 * $pre_parse['rows'] * $pre_parse['cols'] value represent squares.
      // 00 indicates no circle. 80 indicates circle.
      $circle_grid_string = substr($hex, $cricle_grid_start_index, 2 * $pre_parse['rows'] * $pre_parse['cols']);
      $circle_grid_lines = str_split($circle_grid_string, 2 * $pre_parse['cols']);
      $circle_grid = [];
      foreach($circle_grid_lines as $line) {
        $row = str_split($line, 2);
        foreach ($row as &$hex){
          $hex = hexdec($hex);
        }
        $circle_grid[] = $row;
      }
      return $circle_grid;
    }
  }

}
