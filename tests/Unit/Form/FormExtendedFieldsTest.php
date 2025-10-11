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

    /**
     * @return object{
     *  field_1: string,
     *  field_2: string,
     *  field_3: string,
     *  field_4: string
     * }
     */
    public function getFormFieldsExpected(): object
    {
        return new class {
            public string $field_1 = 'field_1';
            public string $field_2 = 'field_2';
            public string $field_3 = 'field_3';
            public string $field_4 = 'field_4';
        };
    }

    #[Test]
    public function itShouldGenerateAClassWithAllFormFields(): void
    {
        $expected = $this->getFormFieldsExpected();
        $return = $this->object->generateAnObjectWithFields(FormFieldsEnum::cases());

        self::assertCount(4, (array) $return);
        self::assertObjectHasProperty('field_1', $return);
        self::assertObjectHasProperty('field_2', $return);
        self::assertObjectHasProperty('field_3', $return);
        self::assertObjectHasProperty('field_4', $return);
        self::assertEquals($expected->field_1, $return->field_1);
        self::assertEquals($expected->field_2, $return->field_2);
        self::assertEquals($expected->field_3, $return->field_3);
        self::assertEquals($expected->field_4, $return->field_4);
    }
}
