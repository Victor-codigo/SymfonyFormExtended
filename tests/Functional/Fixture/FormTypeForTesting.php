<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Functional\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use VictorCodigo\SymfonyFormExtended\Form\FormMessage;
use VictorCodigo\SymfonyFormExtended\Type\FormTypeBase;
use VictorCodigo\SymfonyFormExtended\Type\FormTypeExtendedInterface;

/**
 * @extends FormTypeBase<FormTypeForTesting>
 *
 * @implements FormTypeExtendedInterface<FormTypeForTesting>
 */
class FormTypeForTesting extends FormTypeBase implements FormTypeExtendedInterface
{
    public const string TRANSLATION_DOMAIN = 'TranslationDomain';
    public const string CSRF_TOKEN_ID = 'CsrfTokenId';
    public const string CSRF_TOKEN_NAME = 'CsrfTokenName';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('csrf_token_id', self::CSRF_TOKEN_ID);
        $resolver->setDefault('data_class', FormValidationClassForTesting::class);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
    }

    /**
     * @return Collection<array-key, FormMessage>
     */
    public function getFormSuccessMessages(): Collection
    {
        $messagesOk = [];

        return new ArrayCollection($messagesOk);
    }
}
