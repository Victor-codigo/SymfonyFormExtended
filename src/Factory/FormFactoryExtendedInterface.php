<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Factory;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedInterface;

interface FormFactoryExtendedInterface extends FormFactoryInterface
{
    /**
     * Returns a form, for translated messages.
     *
     * @see createNamedBuilder()
     *
     * @param mixed                $data    The initial data
     * @param array<string, mixed> $options
     *
     * @throws InvalidOptionsException if any given option is not applicable to the given type
     */
    public function createNamedTranslated(string $name, string $type, ?string $locale = null, mixed $data = null, array $options = []): FormExtendedInterface;
}
