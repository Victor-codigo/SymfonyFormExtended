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
        return (object) $this->getFormFieldsNames($formFields);
    }

    /**
     * @param array<int, \BackedEnum> $formFields If a field ends in "[]", it is supposed that it is an array of fields
     */
    public function generateAnObjectWithFormNameAndFields(string $formName, array $formFields): object
    {
        return (object) $this->getFieldNamesWithFormName($formName, $formFields);
    }

    /**
     * @param array<int, \BackedEnum> $formFields If a field ends in "[]", it is supposed that it is an array of fields
     *
     * @return array<string, string>
     */
    private function getFieldNamesWithFormName(string $formName, array $formFields): array
    {
        $fieldsWithFormName = [];
        $fields = $this->getFormFieldsNames($formFields);

        foreach ($fields as $fieldName => $fieldValue) {
            if (str_ends_with($fieldValue, '[]')) {
                $fieldValueAux = rtrim($fieldValue, '[]');
                $fieldsWithFormName[$fieldName] = $formName.'['.$fieldValueAux.'][]';

                continue;
            }

            $fieldsWithFormName[$fieldName] = $formName.'['.$fieldValue.']';
        }

        return $fieldsWithFormName;
    }

    /**
     * @param array<int, \BackedEnum> $formFields
     *
     * @return array<string, string>
     */
    private function getFormFieldsNames(array $formFields): array
    {
        $fields = [];

        foreach ($formFields as $field) {
            /** @var string */
            $fieldValue = $field->value;
            $fields[mb_strtolower($field->name)] = $fieldValue;
        }

        return $fields;
    }
}
