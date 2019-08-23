<?php

namespace Drupal\crossword\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\crossword\CrosswordFileParserManagerInterface;
use Drupal\file\Entity\File;

/**
 * Validates the crossword file.
 */
class CrosswordFileValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Crossword Parser manager service.
   *
   * @var \Drupal\crossword\CrosswordFileParserManagerInterface
   */
  protected $parserManager;

  /**
   * Create an instance of the validator.
   *
   * @param \Drupal\crossword\CrosswordFileParserManagerInterface $parser_manager
   *   The parser manager service.
   */
  public function __construct(CrosswordFileParserManagerInterface $parser_manager) {
    $this->parserManager = $parser_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crossword.manager.parser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    $allowed_parsers = $items->getFieldDefinition()->getSetting('allowed_parsers');
    $allowed_parser_definitions = $this->parserManager->loadDefinitionsFromOptionList($allowed_parsers);

    foreach ($items as $item) {
      if (get_class($item) == "Drupal\Core\TypedData\Plugin\DataType\IntegerData") {
        $file = File::load($item->getCastedValue());
        if (FALSE === $this->parserManager->filterApplicableDefinitions($allowed_parser_definitions, $file)) {
          $this->context->addViolation($constraint->noParser);
        }
      }
    }
  }

}
