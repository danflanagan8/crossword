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

  public $noParser = 'There is no existing CrosswordFileParser Plugin that can parse this file.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\crossword\Plugin\Validation\Constraint\CrosswordFileValidator';
  }

}
