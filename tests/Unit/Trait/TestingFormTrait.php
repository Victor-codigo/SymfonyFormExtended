<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\UploadFile\Adapter\UploadedFileSymfonyAdapter;

trait TestingFormTrait
{
    /**
     * @return Collection<int, FormError>
     */
    private function createErrors(): Collection
    {
        return FormTypeForTesting::getFormErrors();
    }

    /**
     * Adds to error message the string ".translated".
     *
     * @param Collection<int, FormError> $errors
     * @param FormInterface<mixed>       $form
     *
     * @return Collection<int, FormError>
     */
    private function getErrorsTranslated(Collection $errors, FormInterface $form): Collection
    {
        return $errors->map(function (FormError $error) use ($form): FormError {
            $error = new FormError(
                $error->getMessage().'.translated',
                $error->getMessageTemplate(),
                $error->getMessageParameters(),
                $error->getMessagePluralization(),
                $error->getCause()
            );
            $error->setOrigin($form);

            return $error;
        });
    }

    /**
     * @param FormInterface<mixed>&MockObject        $form
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
     * @param Collection<int, FormError> $errors
     * @param Collection<int, FormError> $errorsTranslated
     */
    private function createSubFormMethodTrans(Collection $errors, Collection $errorsTranslated): void
    {
        $transInvokeCount = $this->exactly($errors->count());
        $this->translator
            ->expects($transInvokeCount)
            ->method('trans')
            ->with(
                self::callback(function (string $message) use ($transInvokeCount, $errors): bool {
                    self::assertEquals($errors->get($transInvokeCount->numberOfInvocations() - 1)?->getMessage(), $message);

                    return true;
                }),
                self::callback(function (array $params) use ($transInvokeCount, $errors): bool {
                    self::assertEquals($errors->get($transInvokeCount->numberOfInvocations() - 1)?->getMessageParameters(), $params);

                    return true;
                }),
                self::equalTo(FormTypeForTesting::TRANSLATION_DOMAIN),
                self::equalTo($this->locale)
            )
            ->willReturnCallback(fn (): string => $errorsTranslated->get($transInvokeCount->numberOfInvocations() - 1)?->getMessage() ?: throw new \Exception('Method trans should not return null'));
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
