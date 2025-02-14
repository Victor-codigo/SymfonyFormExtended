<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormTypeInterface;

/**
 * @template TData
 *
 * @extends FormTypeInterface<TData>
 */
interface FormTypeExtendedInterface extends FormTypeInterface
{
    public const string TRANSLATION_DOMAIN = '';

    /**
     * @return Collection<array-key, FormError>
     */
    public function getFormSuccessMessages(): Collection;
}
