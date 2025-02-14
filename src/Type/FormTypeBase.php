<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormTypeExtendedInterface;

/**
 * @template TData
 *
 * @template-extends AbstractType<TData>
 *
 * @template-implements FormTypeExtendedInterface<TData>
 */
abstract class FormTypeBase extends AbstractType implements FormTypeExtendedInterface
{
    public const string TRANSLATION_DOMAIN = '';
    protected const string CSRF_TOKEN_ID = '';
    protected const string CSRF_TOKEN_NAME = '';

    public function __construct(
        protected TranslatorInterface $translator,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('csrf_protection', true);
        $resolver->setDefault('csrf_field_name', static::CSRF_TOKEN_NAME);
    }

    public function getCsrfToken(): string
    {
        return $this->csrfTokenManager->getToken(static::CSRF_TOKEN_ID)->getValue();
    }

    public function getCsrfTokenFieldName(): string
    {
        return self::CSRF_TOKEN_NAME;
    }
}
