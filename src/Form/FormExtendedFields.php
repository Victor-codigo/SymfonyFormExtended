<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

class FormExtendedFields
{
    /**
     * @param array<int, \BackedEnum> $formFields
     */
    public function generateAnObjectWithFields(array $formFields): object
    {
        $fields = [];

        foreach ($formFields as $field) {
            $fields[mb_strtolower($field->name)] = $field->value;
        }

        return (object) $fields;
    }
}
