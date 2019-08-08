<?php

namespace Drupal\crossword\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\crossword\CrosswordFileParser;
use Drupal\file\Entity\File;

/**
 * Validates the crossword file
 */
class CrosswordFileValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    foreach ($items as $item) {
      if (get_class($item) == "Drupal\Core\TypedData\Plugin\DataType\IntegerData") {
        $file = File::load($item->getCastedValue());
        $parser = new CrosswordFileParser($file);
        $errors = $parser->validationErrors();
        if (!empty($errors)) {
          if (isset($errors['missing'])) {
            foreach($errors['missing'] as $tag) {
              $this->context->addViolation($constraint->missingTag, ['%tag' => $tag]);
            }
          }
          if (isset($errors['extra'])) {
            foreach($errors['extra'] as $tag) {
              $this->context->addViolation($constraint->extraTag, ['%tag' => $tag]);
            }
          }
          if (isset($errors['out_of_order'])) {
            $this->context->addViolation($constraint->outOfOrder, []);
          }
        }
      }

    }
  }
}
