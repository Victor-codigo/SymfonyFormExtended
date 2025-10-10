<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FormExtendedConstraints
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    public function getFormConstraints(string $formValidationClass): object
    {
        /** @var ClassMetadataInterface */
        $metadata = $this->validator->getMetadataFor($formValidationClass);

        return $this->getFormPropertiesWithConstraints($metadata);
    }

    private function getFormPropertiesWithConstraints(ClassMetadataInterface $metaData): object
    {
        $formConstraints = [];
        $propertiesWithConstraints = $metaData->getConstrainedProperties();

        foreach ($propertiesWithConstraints as $property) {
            foreach ($metaData->getPropertyMetadata($property) as $propertyMetaData) {
                $formConstraints[$property] = $this->getPropertyConstraints($propertyMetaData);
            }
        }

        return (object) $formConstraints;
    }

    private function getPropertyConstraints(PropertyMetadataInterface $propertyMetadata): object
    {
        $propertyConstraintNames = [];
        $propertyConstraints = $propertyMetadata->getConstraints();

        foreach ($propertyConstraints as $constraint) {
            $constraintsToAdd = $constraint;

            if (property_exists($constraint, 'constraints')) {
                // @phpstan-ignore argument.type
                $constraintsNested = $this->getNestedConstraints($constraint->constraints);
                $constraintsToAdd = $constraintsNested;
            }

            $constraintName = $this->getPropertyConstraintName($constraint);
            $propertyConstraintNames[$constraintName] = $constraintsToAdd;
        }

        return (object) $propertyConstraintNames;
    }

    /**
     * @param array<Constraint> $constraints
     */
    private function getNestedConstraints(array $constraints): object
    {
        $constraintsNested = [];

        foreach ($constraints as $constraint) {
            $constraintName = $this->getPropertyConstraintName($constraint);
            $constraintsNested[$constraintName] = $constraint;
        }

        return (object) $constraintsNested;
    }

    private function getPropertyConstraintName(Constraint $constraint): string
    {
        $constraintClassNameParts = explode('\\', $constraint::class);
        $constraintName = end($constraintClassNameParts);

        return mb_lcfirst($constraintName);
    }
}
