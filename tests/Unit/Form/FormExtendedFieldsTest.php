<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedFields;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormFieldsEnum;

class FormExtendedFieldsTest extends TestCase
{
    private FormExtendedFields $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new FormExtendedFields();
    }

    #[Test]
    public function itShouldGenerateAClassWithAllFormFields(): void
    {
        $return = $this->object->generateAnObjectWithFields(FormFieldsEnum::cases());

        self::assertCount(5, (array) $return);
        self::assertObjectHasProperty('field_1', $return);
        self::assertObjectHasProperty('field_2', $return);
        self::assertObjectHasProperty('field_3', $return);
        self::assertObjectHasProperty('field_4', $return);
        self::assertObjectHasProperty('field_5', $return);
        self::assertEquals('field_1', $return->field_1);
        self::assertEquals('field_2', $return->field_2);
        self::assertEquals('field_3', $return->field_3);
        self::assertEquals('field_4[]', $return->field_4);
        self::assertEquals('field_5[]', $return->field_5);
    }

    #[Test]
    public function itShouldGenerateAClassWithAllFormFieldsWithFormName(): void
    {
        $formName = 'form_name';
        $return = $this->object->generateAnObjectWithFormNameAndFields($formName, FormFieldsEnum::cases());

        self::assertCount(5, (array) $return);
        self::assertObjectHasProperty('field_1', $return);
        self::assertObjectHasProperty('field_2', $return);
        self::assertObjectHasProperty('field_3', $return);
        self::assertObjectHasProperty('field_4', $return);
        self::assertObjectHasProperty('field_5', $return);
        self::assertEquals($formName.'[field_1]', $return->field_1);
        self::assertEquals($formName.'[field_2]', $return->field_2);
        self::assertEquals($formName.'[field_3]', $return->field_3);
        self::assertEquals($formName.'[field_4][]', $return->field_4);
        self::assertEquals($formName.'[field_5][]', $return->field_5);
    }
}
