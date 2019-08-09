<?php

namespace Drupal\crossword;

class CrosswordFileParser {

  public $file;

  private $contents;

  private $lines;

  public function __construct($file) {
    $this->file = $file;
    $this->contents = file_get_contents($file->getFileUri());
    $this->contents = trim($this->contents);
    $this->lines = explode("\n", $this->contents);
  }

  /**
   * Checks for missing tags, extra tags, oout of order tags.
   */
  public function validationErrors() {
    $missing_tags = [];
    $extra_tags = [];
    $required_tags = [
      "<GRID>",
      "<ACROSS>",
      "<DOWN>",
      "<NOTEPAD>",
    ];
    $expected_order = [
      "<TITLE>",
      "<AUTHOR>",
      "<COPYRIGHT>",
      "<SIZE>",
      "<GRID>",
      "<REBUS>",
      "<ACROSS>",
      "<DOWN>",
      "<NOTEPAD>",
    ];

    $matches = [];
    preg_match_all("/<[A-Z]+?>/", $this->contents, $matches);
    $actual_tags = $matches[0];

    foreach ($required_tags as $tag) {
      if (array_search($tag, $actual_tags) === FALSE) {
        $missing_tags[] = $tag;
      }
    }
    foreach ($actual_tags as $tag) {
      if (array_search($tag, $expected_order) === FALSE) {
        $extra_tags[] = $tag;
      }
    }
    if (!empty($missing_tags) || !empty($extra_tags)) {
      return [
        'missing' => $missing_tags,
        'extra' => $extra_tags,
      ];
    }


    $relevant_tags = [];
    foreach ($expected_order as $tag) {
      if (array_search($tag, $actual_tags) > -1) {
        $relevant_tags[] = $tag;
      }
    }
    foreach($relevant_tags as $index => $tag) {
      if ($relevant_tags[$index] != $actual_tags[$index]) {
        return [
          'out_of_order' => TRUE,
        ];
      }
    }
    return [];
  }

  public function parse() {
    return [
      'title' => $this->getTitle(),
      'author' => $this->getAuthor(),
      'puzzle' => $this->getGridAndClues(),
    ];
  }

  public function getTitle() {
    return $this->lines[array_search("<TITLE>", $this->lines) + 1];
  }

  public function getAuthor() {
    return $this->lines[array_search("<AUTHOR>", $this->lines) + 1];
  }

  public function getGridAndClues() {
    $grid = [];
    $clues = [
      'across' => [],
      'down' => [],
    ];

    $raw_clues = $this->getRawClues();
    $raw_grid = $this->getRawGrid();

    $iterator = [
      'index_across' => -1,
      'index_down' => -1,
      'numeral' => 0,
    ];

    $rebus_array = $this->getRebusArray();

    foreach ($raw_grid as $row_index => $raw_row) {
      $row = [];
      for ($col_index = 0; $col_index < count($raw_row); $col_index++) {
        $fill = $raw_row[$col_index];
        $square = [];
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
              $numeral = $iterator['numeral'];
              $clues['across'][] = [
                'text' => $raw_clues['across'][$iterator['index_across']],
                'numeral' => $iterator['numeral'],
                'references' => $this->findReferences($raw_clues['across'][$iterator['index_across']]),
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
              if (!$numeral_incremented) {
                $iterator['numeral']++;
              }
              $numeral = $iterator['numeral'];
              $clues['down'][] = [
                'text' => $raw_clues['down'][$iterator['index_down']],
                'numeral' => $iterator['numeral'],
                'references' => $this->findReferences($raw_clues['down'][$iterator['index_down']]),
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

  private function addSquareMoves(&$grid, $clues) {
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
    foreach ($clues['down'] as $index => $down_clue) {
      if (!empty($down_clue['references'])) {
        if (!empty($down_clue['references']['across'])) {
          $down_clue['references']['across']['index'] = [];
          foreach ($down_clue['references']['across']['numeral'] as $numeral) {
            foreach($clues['across'] as $key => $clue) {
              if ($clue['numeral'] == $numeral) {
                $down_clue['references']['across']['index'][] = $key;
              }
            }
          }
        }
        if (!empty($down_clue['references']['down'])) {
          $down_clue['references']['down']['index'] = [];
          foreach ($down_clue['references']['down']['numeral'] as $numeral) {
            foreach($clues['down'] as $key => $clue) {
              if ($clue['numeral'] == $numeral) {
                $down_clue['references']['down']['index'][] = $key;
              }
            }
          }
        }
        $clues['down'][$index] = $down_clue;
      }
    }
    foreach ($clues['across'] as $index => $across_clue) {
      if (!empty($across_clue['references'])) {
        if (!empty($across_clue['references']['across'])) {
          $across_clue['references']['across']['index'] = [];
          foreach ($across_clue['references']['across']['numeral'] as $numeral) {
            foreach($clues['across'] as $key => $clue) {
              if ($clue['numeral'] == $numeral) {
                $across_clue['references']['across']['index'][] = $key;
              }
            }
          }
        }
        if (!empty($across_clue['references']['down'])) {
          $across_clue['references']['down']['index'] = [];
          foreach ($across_clue['references']['down']['numeral'] as $numeral) {
            foreach($clues['down'] as $key => $clue) {
              if ($clue['numeral'] == $numeral) {
                $across_clue['references']['down']['index'][] = $key;
              }
            }
          }
        }
        $clues['across'][$index] = $across_clue;
      }
    }
  }

  public function getRawClues() {
    $across_clues_start_index = array_search("<ACROSS>", $this->lines) + 1;
    $down_clues_start_index = array_search("<DOWN>", $this->lines) + 1;

    $across_clues_number = $down_clues_start_index - $across_clues_start_index - 1;
    $across_clues = array_slice($this->lines, $across_clues_start_index, $across_clues_number);

    if (array_search("<NOTEPAD>", $this->lines) > -1) {
      $down_clues_number = array_search("<NOTEPAD>", $this->lines) - $down_clues_start_index;
      $down_clues = array_slice($this->lines, $down_clues_start_index, $down_clues_number);
    }
    else {
      $down_clues = array_slice($this->lines, $down_clues_start_index);
    }

    return [
      'across' => $across_clues,
      'down' => $down_clues,
    ];
  }

  public function getRawGrid() {
    $raw_grid = [];

    $grid_start_index = array_search("<GRID>", $this->lines) + 1;
    $after_grid_index = array_search("<REBUS>", $this->lines) > -1 ? array_search("<REBUS>", $this->lines) : array_search("<ACROSS>", $this->lines);
    $number_of_rows = $after_grid_index - $grid_start_index;
    $grid_lines = array_slice($this->lines, $grid_start_index, $number_of_rows);

    foreach ($grid_lines as $row_index => $grid_line) {
      for ($col_index = 0; $col_index < strlen($grid_line); $col_index++) {
        $raw_grid[$row_index][] = $grid_line[$col_index] !== "." ? $grid_line[$col_index] : NULL;
      }
    }
    return $raw_grid;
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
          $references['down']['numeral'][] = $ref_num;
          $i++;
        }
      }
      if( $down_index === FALSE ){
        //just across references
        $i = 0;
        while( $i < $across_index ){
          $ref_num = str_replace("-", "", $matches[$i]);
          $references['across']['numeral'][] = $ref_num;
          $i++;
        }
      }
      if( $across_index > -1 && $down_index > -1 ){
        //assume Across references are first, as they should be
        //across
        $i = 0;
        while( $i < $across_index ){
          $ref_num = str_replace("-", "", $matches[$i]);
          $references['across']['numeral'][] = $ref_num;
          $i++;
        }
        //now down. We have to move past the acrossIndex
        $i = $across_index + 1;
        while( $i < $down_index ){
          $ref_num = str_replace("-", "", $matches[$i]);
          $references['down']['numeral'][] = $ref_num;
          $i++;
        }
      }
      return $references;
    }
  }

  private function getRebusArray() {
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
