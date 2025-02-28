<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Type;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormTypeInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormMessage;

/**
 * @template TData
 *
 * @extends FormTypeInterface<TData>
 */
interface FormTypeExtendedInterface extends FormTypeInterface
{
    public const string TRANSLATION_DOMAIN = '';

    /**
     * @return Collection<array-key, FormMessage>
     */
    public function getFormSuccessMessages(): Collection;
}
