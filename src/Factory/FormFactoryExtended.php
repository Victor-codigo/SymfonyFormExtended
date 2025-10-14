<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Factory;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use VictorCodigo\SymfonyFormExtended\Form\FormExtended;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedInterface;

/**
 * @template TData
 */
class FormFactoryExtended implements FormFactoryExtendedInterface
{
    private FormFactoryInterface $formFactory;
    private FormExtendedFactory $formExtendedFactory;

    /**
     * @throws \LogicException
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        FormExtendedFactory $formExtendedFactory,
        RequestStack $request,
    ) {
        $this->formFactory = $formFactory;
        $this->formExtendedFactory = $formExtendedFactory;
        $session = $request->getSession();

        if (!$session instanceof Session) {
            throw new \LogicException('FormFactoryExtended needs to have a session available.');
        }
    }

    /**
     * Returns a form, for translated messages.
     *
     * @see createNamedBuilder()
     *
     * @param mixed                $data    The initial data
     * @param array<string, mixed> $options
     *
     * @return FormExtendedInterface<TData>
     *
     * @throws InvalidOptionsException if any given option is not applicable to the given type
     */
    public function createNamedExtended(string $name, string $type, ?string $locale = null, mixed $data = null, array $options = []): FormExtendedInterface
    {
        $builder = $this->createNamedBuilder($name, $type, $data, $options);
        /** @var FormInterface<mixed> */
        $form = $builder->getForm();

        return new FormExtended($this->formExtendedFactory, $locale);
    }

    /**
     * @return FormInterface<TData>
     */
    public function create(string $type = FormType::class, mixed $data = null, array $options = []): FormInterface
    {
        /** @var FormInterface<TData> */
        $form = $this->formFactory->create($type, $data, $options);

        return $form;
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return FormBuilderInterface<TData>
     */
    public function createBuilder(string $type = FormType::class, mixed $data = null, array $options = []): FormBuilderInterface
    {
        return $this->formFactory->createBuilder($type, $data, $options);
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return FormBuilderInterface<TData>
     */
    public function createBuilderForProperty(string $class, string $property, mixed $data = null, array $options = []): FormBuilderInterface
    {
        return $this->formFactory->createBuilderForProperty($class, $property, $data, $options);
    }

    /**
     * @param array<array-key, mixed> $options
     *
     * @return FormInterface<TData>
     */
    public function createForProperty(string $class, string $property, mixed $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->createForProperty($class, $property, $data, $options);
    }

    /**
     * @return FormInterface<TData>
     */
    public function createNamed(string $name, string $type = FormType::class, mixed $data = null, array $options = []): FormInterface
    {
        /** @var FormInterface<TData> */
        $form = $this->formFactory->createNamed($name, $type, $data, $options);

        return $form;
    }

    /**
     * @param array<array-key, TData> $options
     *
     * @return FormBuilderInterface<TData>
     */
    public function createNamedBuilder(string $name, string $type = FormType::class, mixed $data = null, array $options = []): FormBuilderInterface
    {
        return $this->formFactory->createNamedBuilder($name, $type, $data, $options);
    }
}
