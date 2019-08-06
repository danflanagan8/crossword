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

    foreach ($raw_grid as $row_index => $raw_row) {
      $row = [];
      for ($col_index = 0; $col_index < count($raw_row); $col_index++) {
        $fill = $raw_row[$col_index];

        if ($fill === NULL) {
          $row[] = [
            'fill' => NULL,
          ];
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
              ];
              $numeral_incremented = TRUE;
            }
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
              ];
              $numeral_incremented = TRUE;
            }
          }

          $row[] = [
            'fill' => $fill,
            'across' => [
              'index' => $iterator['index_across'],
            ],
            'down' => [
              'index' => $iterator['index_down'],
            ],
            'numeral' => $numeral,
          ];

        }

      }
      $grid[] = $row;
    }

    return [
      'grid' => $grid,
      'clues' => $clues,
    ];
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

}
