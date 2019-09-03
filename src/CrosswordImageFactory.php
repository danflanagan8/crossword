<?php

namespace Drupal\crossword;

use Drupal\file\FileInterface;
use Drupal\Core\File\FileSystem;

/**
 * A Class to manage generating images from a crossword file.
 */
class CrosswordImageFactory {

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The crossword file parser plugin manage.
   *
   * @var \Drupal\crossword\CrosswordFileParserManager
   */
  protected $parserManager;

  /**
   * Construct the Crossword Image Factory.
   */
  public function __construct(FileSystem $file_system, CrosswordFileParserManager $parser_manager) {
    $this->fileSystem = $file_system;
    $this->parserManager = $parser_manager;
  }

  /**
   * Returns the uri to a thumbanil preview for the crossword file.
   *
   * @param Drupal\file\FileInterface $file
   *   The file representing the crossword puzzle.
   *
   * @return resource
   *   An image resource to be used as a thumbnail.
   */
  public function getThumbnailUri(FileInterface $file) {
    $destination_uri = $this->getDestinationUri($file);
    if (file_exists($destination_uri)) {
      // Check if the existing thumbnail is older than the file itself.
      if (filemtime($file->getFileUri()) <= filemtime($destination_uri)) {
        // The existing thumbnail can be used, nothing to do.
        return $destination_uri;
      }
      else {
        // Delete the existing but out-of-date thumbnail.
        $this->fileSystem->delete($destination_uri);
        image_path_flush($destination_uri);
      }
    }
    if ($this->createThumbnail($file, $destination_uri)) {
      return $destination_uri;
    }
  }

  /**
   * Gets the destination URI of the file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file that is being converted.
   * @param string $destination_uri
   *   The destination of the new file.
   *
   * @return bool
   *   Returns TRUE upon success, FALSE upon failure.
   */
  protected function createThumbnail(FileInterface $file, $destination_uri) {
    $parser = $this->parserManager->loadCrosswordFileParserFromInput($file);
    if (!$parser) {
      return NULL;
    }
    $data = $parser->parse();
    $image = $this->getNewThumbnailImage($data);
    ob_start();
    imagejpeg($image);
    $image_data = ob_get_clean();
    $directory = $this->fileSystem->dirname($destination_uri);
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    return $this->fileSystem->saveData($image_data, $destination_uri, FILE_EXISTS_REPLACE);
  }

  /**
   * Returns an image resource representing a preview of the puzzle.
   *
   * @param array $data
   *   The parsed crossword file.
   *
   * @return resource
   *   An image resource to be used as a thumbnail.
   */
  protected function getNewThumbnailImage(array $data) {
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

  /**
   * Gets the destination URI of the file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file that is being converted.
   *
   * @return string
   *   The destination URI.
   */
  protected function getDestinationUri(FileInterface $file) {
    $output_path = file_default_scheme() . '://crossword';
    $filename = "{$file->id()}-thumbnail.jpg";
    return $output_path . '/' . $filename;
  }

}
