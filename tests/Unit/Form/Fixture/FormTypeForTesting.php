<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    public const string  TRANSLATION_DOMAIN = 'FormTypeForTesting';

    /**
     * @return Collection<array-key, FormMessage>
     */
    public function getFormSuccessMessages(): Collection
    {
        return self::getFormMessageErrors();
    }

    /**
     * @return Collection<array-key, FormMessage>
     */
    public static function getFormMessageErrors(): Collection
    {
        return new ArrayCollection([
            new FormMessage(
                'message.error.msg1',
                'messageTemplate.error.msg1',
                [
                    'param1' => 'value1',
                    'param2' => 'value2',
                ],
                null,
            ),
            new FormMessage(
                'message.error.msg2',
                'messageTemplate.error.msg2',
                [
                    'param1' => 'value1',
                    'param2' => 'value2',
                ],
                1,
            ),
            new FormMessage(
                'message.error.msg3',
                'messageTemplate.error.msg3',
                [
                    'param1' => 'value1',
                ],
                2,
            ),
        ]);
    }
}
