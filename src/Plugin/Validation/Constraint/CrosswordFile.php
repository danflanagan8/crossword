<?php

namespace Drupal\crossword\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted value is a unique integer.
 *
 * @Constraint(
 *   id = "CrosswordFile",
 *   label = @Translation("Crossword File", context = "Validation"),
 *   type = "file"
 * )
 */
class CrosswordFile extends Constraint {

  // The message that will be shown if the value is not an integer.
  public $missingTag = '%tag is missing.';
  public $extraTag = '%tag is not allowed.';
  public $outOfOrder = 'Tags are out of order. Expected order is (* -> required): TITLE, AUTHOR, COPYRIGHT, SIZE, GRID*, REBUS, ACROSS*, DOWN*';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\crossword\Plugin\Validation\Constraint\CrosswordFileValidator';
  }

}
