<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormMessage;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\UploadFile\Adapter\UploadedFileSymfonyAdapter;

trait TestingFormTrait
{
    /**
     * @return Collection<int, FormMessage>
     */
    private function createMessageErrors(): Collection
    {
        return FormTypeForTesting::getFormMessageErrors();
    }

    /**
     * Adds to error message the string ".translated".
     *
     * @param Collection<int, FormMessage> $messageErrors
     * @param FormInterface<Form>          $form
     *
     * @return Collection<int, FormMessage>
     */
    private function getMessageErrorsTranslated(Collection $messageErrors, FormInterface $form): Collection
    {
        return $messageErrors->map(function (FormMessage $error): FormMessage {
            $error = new FormMessage(
                $error->message.'.translated',
                $error->template,
                $error->parameters,
                $error->pluralization,
            );

            return $error;
        });
    }

    /**
     * @param FormInterface<Form>&MockObject         $form
     * @param FormConfigInterface<object>&MockObject $formConfig
     */
    private function createStubForGetInnerType(FormInterface&MockObject $form, FormConfigInterface&MockObject $formConfig, ResolvedFormTypeInterface&MockObject $resolvedFormType, FormTypeForTesting $formType): void
    {
        $form
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig
            ->expects($this->any())
            ->method('getType')
            ->willReturn($resolvedFormType);

        $resolvedFormType
            ->expects($this->any())
            ->method('getInnerType')
            ->willReturn($formType);
    }

    /**
     * @param Collection<int, FormMessage> $messageErrors
     * @param Collection<int, FormMessage> $messageErrorsTranslated
     */
    private function createSubFormMethodTrans(Collection $messageErrors, Collection $messageErrorsTranslated): void
    {
        $transInvokeCount = $this->exactly($messageErrors->count());
        $this->translator
            ->expects($transInvokeCount)
            ->method('trans')
            ->with(
                self::callback(function (string $message) use ($transInvokeCount, $messageErrors): bool {
                    self::assertEquals($messageErrors->get($transInvokeCount->numberOfInvocations() - 1)?->message, $message);

                    return true;
                }),
                self::callback(function (array $params) use ($transInvokeCount, $messageErrors): bool {
                    self::assertEquals($messageErrors->get($transInvokeCount->numberOfInvocations() - 1)?->parameters, $params);

                    return true;
                }),
                self::equalTo(FormTypeForTesting::TRANSLATION_DOMAIN),
                self::equalTo($this->locale)
            )
            ->willReturnCallback(fn (): string => $messageErrorsTranslated->get($transInvokeCount->numberOfInvocations() - 1)?->message ?: throw new \Exception('Method trans should not return null'));
    }

    /**
     * @return Collection<string, UploadedFileSymfonyAdapter&MockObject>
     */
    private function getFormUploadedFilesMock(): Collection
    {
        return new ArrayCollection([
            'image1' => $this->createMock(UploadedFileSymfonyAdapter::class),
            'image2' => $this->createMock(UploadedFileSymfonyAdapter::class),
            'image3' => $this->createMock(UploadedFileSymfonyAdapter::class),
        ]);
    }
}
