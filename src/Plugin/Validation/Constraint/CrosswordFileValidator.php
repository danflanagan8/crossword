<?php

namespace Drupal\crossword\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\crossword\CrosswordFileParser;
use Drupal\crossword\CrosswordFileParserManagerInterface;
use Drupal\file\Entity\File;

/**
 * Validates the crossword file
 */
class CrosswordFileValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Video embed provider manager service.
   *
   * @var \Drupal\crossword\CrosswordFileParserManagerInterface
   */
  protected $providerManager;

  /**
   * Create an instance of the validator.
   *
   * @param \Drupal\crossword\CrosswordFileParserManagerInterface $provider_manager
   *   The provider manager service.
   */
  public function __construct(CrosswordFileParserManagerInterface $provider_manager) {
    $this->providerManager = $provider_manager;
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
    dpm('trying...');
    foreach ($items as $item) {
      if (get_class($item) == "Drupal\Core\TypedData\Plugin\DataType\IntegerData") {
        $file = File::load($item->getCastedValue());
        dpm($file->id());
        $parser = new CrosswordFileParser($file);
        dpm('parser loaded?');
        if(FALSE === $this->providerManager->loadDefinitionFromInput($file)){
          dpm('bad');
          $this->context->addViolation($constraint->no_parser);
        }
      }
    }
    dpm('ok?');
  }
}
