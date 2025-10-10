<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedConstraints;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormExtendedValidationTestDataProvider;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormValidationClassForTesting;

class FormExtendedConstraintsTest extends TestCase
{
    private FormExtendedConstraints $object;
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
        $this->object = new FormExtendedConstraints($this->validator);
    }

    /**
     * @param object{
     *  name: object{
     *      notBlank: object{
     *          message: string
     *      },
     *      length: object{
     *          min: int,
     *          max: int,
     *          minMessage: string,
     *          maxMessage: string
     *      }
     *  },
     *  description: object{
     *      length: object{
     *          max: int,
     *          maxMessage: string
     *      }
     *  },
     *  ingredients: object{
     *      all: object{
     *          notBlank: object{
     *              message: string
     *          },
     *          length: object{
     *              max: int,
     *              maxMessage: string
     *          }
     *      },
     *      count: object{
     *          min: int,
     *          max: int,
     *          minMessage: string,
     *          maxMessage: string
     *      }
     *  },
     *  steps: object{
     *      all: object{
     *          notBlank: object{
     *              message: string
     *          },
     *          length: object{
     *              max: int,
     *              maxMessage: string
     *          }
     *      },
     *      count: object{
     *          min: int,
     *          max: int,
     *          minMessage: string,
     *          maxMessage: string
     *      }
     *  },
     *  image: object{
     *      image: object{
     *          maxSize: int,
     *          minWidth: int,
     *          maxWidth: int,
     *          minHeight: int,
     *          maxHeight: int,
     *          allowLandscape: bool,
     *          allowPortrait: bool,
     *          mimeTypes: array<string>,
     *          maxSizeMessage: string,
     *          minWidthMessage: string,
     *          maxWidthMessage: string,
     *          minHeightMessage: string,
     *          maxHeightMessage: string,
     *          mimeTypesMessage: string,
     *      }
     *  },
     *  preparation_time: object{
     *      greaterThan: object{
     *          value: string,
     *          message: string
     *      },
     *      lessThanOrEqual: object{
     *          value: string,
     *          message: string
     *      }
     *  },
     *  public: object{
     *      choice: object{
     *          choices: array<bool>
     *      }
     *  }
     * } $expected
     */
    #[Test]
    #[DataProviderExternal(FormExtendedValidationTestDataProvider::class, 'dataProvider')]
    public function itShouldGetConstraintsOfAFormValidationClass(object $expected): void
    {
        $return = $this->object->getFormConstraints(FormValidationClassForTesting::class);

        $this->assertConstraintsAreOk($expected, $return);
    }

    private function assertConstraintsAreOk(object $expected, object $actual): void
    {
        // @phpstan-ignore-next-line
        $nameExpected = $expected->name;
        // @phpstan-ignore-next-line
        $nameActual = $actual->name;
        // @phpstan-ignore-next-line
        self::assertSame($nameExpected->notBlank->message, $nameActual->notBlank->message);
        // @phpstan-ignore-next-line
        self::assertSame($nameExpected->length->min, $nameActual->length->min);
        // @phpstan-ignore-next-line
        self::assertSame($nameExpected->length->max, $nameActual->length->max);
        // @phpstan-ignore-next-line
        self::assertSame($nameExpected->length->minMessage, $nameActual->length->minMessage);
        // @phpstan-ignore-next-line
        self::assertSame($nameExpected->length->maxMessage, $nameActual->length->maxMessage);

        // @phpstan-ignore-next-line
        $descriptionExpected = $expected->description;
        // @phpstan-ignore-next-line
        $descriptionActual = $actual->description;
        // @phpstan-ignore-next-line
        self::assertSame($descriptionExpected->length->max, $descriptionActual->length->max);
        // @phpstan-ignore-next-line
        self::assertSame($descriptionExpected->length->maxMessage, $descriptionActual->length->maxMessage);

        // @phpstan-ignore-next-line
        $ingredientsExpected = $expected->ingredients;
        // @phpstan-ignore-next-line
        $ingredientsActual = $actual->ingredients;
        // @phpstan-ignore-next-line
        self::assertSame($ingredientsExpected->all->notBlank->message, $ingredientsActual->all->notBlank->message);
        // @phpstan-ignore-next-line
        self::assertSame($ingredientsExpected->all->length->max, $ingredientsActual->all->length->max);
        // @phpstan-ignore-next-line
        self::assertSame($ingredientsExpected->all->length->maxMessage, $ingredientsActual->all->length->maxMessage);
        // @phpstan-ignore-next-line
        self::assertSame($ingredientsExpected->count->min, $ingredientsActual->count->min);
        // @phpstan-ignore-next-line
        self::assertSame($ingredientsExpected->count->max, $ingredientsActual->count->max);
        // @phpstan-ignore-next-line
        self::assertSame($ingredientsExpected->count->minMessage, $ingredientsActual->count->minMessage);
        // @phpstan-ignore-next-line
        self::assertSame($ingredientsExpected->count->maxMessage, $ingredientsActual->count->maxMessage);

        // @phpstan-ignore-next-line
        $stepsExpected = $expected->steps;
        // @phpstan-ignore-next-line
        $stepsActual = $actual->steps;
        // @phpstan-ignore-next-line
        self::assertSame($stepsExpected->all->notBlank->message, $stepsActual->all->notBlank->message);
        // @phpstan-ignore-next-line
        self::assertSame($stepsExpected->all->length->max, $stepsActual->all->length->max);
        // @phpstan-ignore-next-line
        self::assertSame($stepsExpected->all->length->maxMessage, $stepsActual->all->length->maxMessage);
        // @phpstan-ignore-next-line
        self::assertSame($stepsExpected->count->min, $stepsActual->count->min);
        // @phpstan-ignore-next-line
        self::assertSame($stepsExpected->count->max, $stepsActual->count->max);
        // @phpstan-ignore-next-line
        self::assertSame($stepsExpected->count->minMessage, $stepsActual->count->minMessage);
        // @phpstan-ignore-next-line
        self::assertSame($stepsExpected->count->maxMessage, $stepsActual->count->maxMessage);

        // @phpstan-ignore-next-line
        $imageExpected = $expected->image;
        // @phpstan-ignore-next-line
        $imageActual = $actual->image;
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->maxSize, $imageActual->image->maxSize);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->minWidth, $imageActual->image->minWidth);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->maxWidth, $imageActual->image->maxWidth);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->minHeight, $imageActual->image->minHeight);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->allowLandscape, $imageActual->image->allowLandscape);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->allowPortrait, $imageActual->image->allowPortrait);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->mimeTypes, $imageActual->image->mimeTypes);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->maxSizeMessage, $imageActual->image->maxSizeMessage);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->minWidthMessage, $imageActual->image->minWidthMessage);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->maxWidthMessage, $imageActual->image->maxWidthMessage);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->minHeightMessage, $imageActual->image->minHeightMessage);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->maxHeightMessage, $imageActual->image->maxHeightMessage);
        // @phpstan-ignore-next-line
        self::assertSame($imageExpected->image->mimeTypesMessage, $imageActual->image->mimeTypesMessage);

        // @phpstan-ignore-next-line
        $preparationTimeExpected = $expected->preparation_time;
        // @phpstan-ignore-next-line
        $preparationTimeActual = $actual->preparation_time;
        // @phpstan-ignore-next-line
        self::assertSame($preparationTimeExpected->greaterThan->value, $preparationTimeActual->greaterThan->value);
        // @phpstan-ignore-next-line
        self::assertSame($preparationTimeExpected->greaterThan->message, $preparationTimeActual->greaterThan->message);
        // @phpstan-ignore-next-line
        self::assertSame($preparationTimeExpected->lessThanOrEqual->value, $preparationTimeActual->lessThanOrEqual->value);
        // @phpstan-ignore-next-line
        self::assertSame($preparationTimeExpected->lessThanOrEqual->message, $preparationTimeActual->lessThanOrEqual->message);

        // @phpstan-ignore-next-line
        $publicExpected = $expected->public;
        // @phpstan-ignore-next-line
        $publicActual = $actual->public;
        // @phpstan-ignore-next-line
        self::assertSame($publicExpected->choice->choices, $publicActual->choice->choices);
    }
}
