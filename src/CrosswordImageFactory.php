<?php

namespace Drupal\crossword;

use Drupal\file\FileInterface;

class CrosswordImageFactory {

  public $file;

  public function __construct(FileInterface $file) {
    $this->file = $file;
  }

  /**
   * @return resource
   *   An image resource to be used as a thumbnail.
   */
  public function getThumbnail() {
    $parser_manager = \Drupal::service('crossword.manager.parser');
    $parser = $parser_manager->loadCrosswordFileParserFromInput($this->file);
    $data = $parser->parse();

    $grid = $data['puzzle']['grid'];
    $square_size = 20;
    $width = count($grid[0]) * $square_size + 1;
    $height = count($grid) * $square_size + 1;
    $image = imagecreatetruecolor($width, $height);
    $black = imagecolorallocate($image, 0, 0, 0);
    $white = imagecolorallocate($image, 255, 255, 255);
    foreach ($grid as $row_index => $row) {
      foreach ($row as $col_index => $square) {
        if ($square['fill'] !== NULL) {
          $color = $white;
          imagefilledrectangle($image, $col_index * $square_size + 1, $row_index * $square_size + 1, ($col_index + 1) * $square_size - 1, ($row_index + 1) * $square_size - 1, $color);
        }
      }
    }
    return $image;
  }

}
