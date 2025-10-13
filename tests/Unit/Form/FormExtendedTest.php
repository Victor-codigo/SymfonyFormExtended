<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Form\Exception\FormExtendedDataClassNotSetException;
use VictorCodigo\SymfonyFormExtended\Form\FormExtended;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedConstraints;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedCsrfToken;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedFields;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedUpload;
use VictorCodigo\SymfonyFormExtended\Form\FormMessage;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait\TestingFormTrait;

class FormExtendedTest extends TestCase
{
    use TestingFormTrait;

    /**
     * @var FormInterface<mixed>|MockObject
     */
    private FormInterface&MockObject $form;
    /**
     * @var FormConfigInterface<object>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private TranslatorInterface&MockObject $translator;
    private FlashBagInterface&MockObject $flashBag;
    private FormExtendedCsrfToken&MockObject $formExtendedCsrfToken;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;
    private FormExtendedConstraints&MockObject $constraints;
    private FormExtendedFields&MockObject $formFields;
    private FormExtendedUpload&MockObject $formExtendedUploaded;
    private FormTypeForTesting $formType;
    private string $locale = 'locale';

    protected function setUp(): void
    {
        parent::setUp();

        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->form = $this
            ->getMockBuilder(Form::class)
            ->setConstructorArgs([$this->formConfig])
            ->onlyMethods([
                'getConfig',
                'handleRequest',
                'getName',
                'getData',
                'getIterator',
                'clearErrors',
            ])
            ->getMock();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->constraints = $this->createMock(FormExtendedConstraints::class);
        $this->formFields = $this->createMock(FormExtendedFields::class);
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $this->formExtendedCsrfToken = $this->createMock(FormExtendedCsrfToken::class);
        $this->formExtendedUploaded = $this->createMock(FormExtendedUpload::class);
        $this->formType = new FormTypeForTesting($this->translator);
    }

    private function createFormExtended(): FormExtended
    {
        return new FormExtended(
            $this->form,
            $this->translator,
            $this->flashBag,
            $this->constraints,
            $this->formFields,
            $this->formExtendedCsrfToken,
            $this->formExtendedUploaded,
            $this->locale,
        );
    }

    #[Test]
    public function itShouldFailCreatingFormExtendedFormTypeNotImplementsInterfaceFormTypeExtendedInterface(): void
    {
        $formTypeNotImplementsFormTypeExtendedInterface = $this->createMock(FormType::class);

        $this->formConfig
            ->expects($this->any())
            ->method('getType')
            ->willReturn($this->resolvedFormType);

        $this->resolvedFormType
            ->expects($this->any())
            ->method('getInnerType')
            ->willReturn($formTypeNotImplementsFormTypeExtendedInterface);

        $this->expectException(\LogicException::class);
        $this->createFormExtended();
    }

    #[Test]
    public function itShouldGetErrorsTranslatedDeepAndFlattenAreTrue(): void
    {
        $deep = true;
        $flatten = true;
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageErrors): FormExtended => $object->addError(new FormError(
            $messageErrors->message,
            $messageErrors->template,
            $messageErrors->parameters,
            $messageErrors->pluralization,
            $object
        )));
        $messageErrorsTranslated = $this->getMessageErrorsTranslated(
            new ArrayCollection(iterator_to_array($object->getMessageErrorsTranslated(true))),
            $this->form
        );
        $this->createSubFormMethodTrans($messageErrors, $messageErrorsTranslated);

        $return = $object->getMessageErrorsTranslated($deep, $flatten);

        self::assertCount($messageErrorsTranslated->count(), $return);
        $errorTranslated = $messageErrorsTranslated->first();
        foreach ($return as $errorReturned) {
            self::assertEquals($errorTranslated, $errorReturned);

            $errorTranslated = $messageErrorsTranslated->next();
        }
    }

    #[Test]
    public function itShouldGetErrorsTranslatedDeepAndFlattenIAreFalse(): void
    {
        $deep = false;
        $flatten = false;
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageError): FormExtended => $object->addError(new FormError(
            $messageError->message,
            $messageError->template,
            $messageError->parameters,
            $messageError->pluralization
        )));
        $messageErrorsTranslated = $this->getMessageErrorsTranslated(
            new ArrayCollection(iterator_to_array($object->getMessageErrorsTranslated(true))),
            $this->form
        );

        $translationDomain = $this->exactly($messageErrors->count());
        $this->translator
            ->expects($translationDomain)
            ->method('trans')
            ->with(
                self::callback(function (string $message) use ($translationDomain, $messageErrors): bool {
                    self::assertEquals($messageErrors->get($translationDomain->numberOfInvocations() - 1)?->message, $message);

                    return true;
                }),
                self::callback(function (array $params) use ($translationDomain, $messageErrors): bool {
                    self::assertEquals($messageErrors->get($translationDomain->numberOfInvocations() - 1)?->parameters, $params);

                    return true;
                }),
                self::equalTo(FormTypeForTesting::TRANSLATION_DOMAIN),
                self::equalTo($this->locale)
            )
            ->willReturnOnConsecutiveCalls(
                $messageErrorsTranslated->get(0)?->message,
                $messageErrorsTranslated->get(1)?->message,
                $messageErrorsTranslated->get(2)?->message,
                $messageErrorsTranslated->get(3)?->message,
            );

        $return = $object->getMessageErrorsTranslated($deep, $flatten);

        self::assertCount($messageErrorsTranslated->count(), $return);
        $errorTranslated = $messageErrorsTranslated->first();
        foreach ($return as $errorReturned) {
            self::assertEquals($errorTranslated, $errorReturned);

            $errorTranslated = $messageErrorsTranslated->next();
        }
    }

    #[Test]
    public function itShouldNoErrorsFormHasNoErrors(): void
    {
        $deep = true;
        $flatten = true;

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $object = $this->createFormExtended();
        $return = $object->getMessageErrorsTranslated($deep, $flatten);

        self::assertCount(0, $return);
    }

    #[Test]
    public function itShouldGetMessagesOkTranslated(): void
    {
        $messagesOk = FormTypeForTesting::getFormMessageErrors();
        $messageErrorsTranslated = $this->getMessageErrorsTranslated($messagesOk, $this->form);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $this->createSubFormMethodTrans($messagesOk, $messageErrorsTranslated);

        $object = $this->createFormExtended();
        $return = $object->getMessagesSuccessTranslated();

        self::assertEquals($messageErrorsTranslated, $return);
    }

    #[Test]
    public function itShouldAddSuccessFormFlashMessages(): void
    {
        $flashBagSuccessType = 'success';
        $flashBagErrorType = 'error';
        $formSuccessMessages = $this->formType->getFormSuccessMessages();
        $formSuccessMessagesTranslated = $this->getMessageErrorsTranslated($formSuccessMessages, $this->form);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $this->createSubFormMethodTrans($formSuccessMessages, $formSuccessMessagesTranslated);

        $flashBagAddInvokeCount = $this->exactly($formSuccessMessages->count());
        $this->flashBag
            ->expects($flashBagAddInvokeCount)
            ->method('add')
            ->with(
                $flashBagSuccessType,
                self::callback(function (FormMessage $message) use ($formSuccessMessagesTranslated, $flashBagAddInvokeCount): bool {
                    self::assertEquals($formSuccessMessagesTranslated->get($flashBagAddInvokeCount->numberOfInvocations() - 1)?->message, $message->message);

                    return true;
                }));

        $object = $this->createFormExtended();
        $object->addFlashMessagesTranslated($flashBagSuccessType, $flashBagErrorType, true);
    }

    #[Test]
    public function itShouldAddErrorFormFlashMessages(): void
    {
        $flashBagSuccessType = 'success';
        $flashBagErrorType = 'error';
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageError): FormExtended => $object->addError(new FormError(
            $messageError->message,
            $messageError->template,
            $messageError->parameters,
            $messageError->pluralization
        )));
        $errorsTranslated = $this->getMessageErrorsTranslated($messageErrors, $this->form);

        $flashBagAddInvokeCount = $this->exactly($messageErrors->count());
        $this->flashBag
            ->expects($flashBagAddInvokeCount)
            ->method('add')
            ->with(
                $flashBagErrorType,
                self::callback(function (FormMessage $message) use ($errorsTranslated, $flashBagAddInvokeCount): bool {
                    self::assertEquals($errorsTranslated->get($flashBagAddInvokeCount->numberOfInvocations() - 1)?->message, $message->message);

                    return true;
                }));

        $this->createSubFormMethodTrans($messageErrors, $errorsTranslated);

        $object->addFlashMessagesTranslated($flashBagSuccessType, $flashBagErrorType, true);
    }

    #[Test]
    public function itShouldGetFlashMessagesData(): void
    {
        $messagesType = 'messages.type';
        $messagesData = new ArrayCollection([
            new FormMessage('message 1 ', 'template 1', ['param 1' => 'value 1'], null),
            new FormMessage('message 2 ', 'template 2', ['param 2' => 'value 2'], null),
        ]);
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        $this->flashBag
            ->expects($this->once())
            ->method('get')
            ->with($messagesType)
            ->willReturn($messagesData->toArray());

        $return = $object->getFlashMessagesData($messagesType);

        self::assertEquals($messagesData, $return);
    }

    #[Test]
    public function itShouldGetFlashMessages(): void
    {
        $messagesType = 'messages.type';
        $messagesData = new ArrayCollection([
            new FormMessage('message 1 ', 'template 1', ['param 1' => 'value 1'], null),
            new FormMessage('message 2 ', 'template 2', ['param 2' => 'value 2'], null),
        ]);
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        $this->flashBag
            ->expects($this->once())
            ->method('get')
            ->with($messagesType)
            ->willReturn($messagesData->toArray());

        $return = $object->getFlashMessages($messagesType);

        self::assertEquals(
            $messagesData->map(fn (FormMessage $message): string => $message->message),
            $return,
        );
    }

    #[Test]
    public function itShouldGetTheIterator(): void
    {
        $iterator = new \ArrayIterator();
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        // @phpstan-ignore phpunit.mockMethod
        $this->form
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $return = $object->getIterator();

        self::assertEquals($iterator, $return);
    }

    #[Test]
    public function itShouldFailGettingTheIteratorNotImplementsIteratorAggregate(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $objectReflection = new \ReflectionClass($object);
        $objectReflection
            ->getProperty('form')
            ->setValue($object, $this->createMock(FormInterface::class));

        $this->expectException(LogicException::class);
        $object->getIterator();
    }

    #[Test]
    public function itShouldClearErrors(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        // @phpstan-ignore phpunit.mockMethod
        $this->form
            ->expects($this->once())
            ->method('clearErrors')
            ->with(true);

        $return = $object->clearErrors(true);

        self::assertEquals($object, $return);
    }

    #[Test]
    public function itShouldClearingErrorsNotImplementsClearableErrorsInterface(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $objectReflection = new \ReflectionClass($object);
        $objectReflection
            ->getProperty('form')
            ->setValue($object, $this->createMock(FormInterface::class));

        $this->expectException(LogicException::class);
        $object->clearErrors(true);
    }

    #[Test]
    public function itShouldGetFormConstraints(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $dataClass = 'formDataClass';
        $constraintsExpected = new class {
            public object $notBlank;
            public object $length;
        };

        $this->form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formConfig
            ->expects(self::once())
            ->method('getDataClass')
            ->willReturn($dataClass);

        $this->constraints
            ->expects(self::once())
            ->method('getFormConstraints')
            ->with($dataClass)
            ->willReturn($constraintsExpected);

        $return = $object->getConstraints();

        self::assertEquals($constraintsExpected, $return);
    }

    #[Test]
    public function itShouldFailGetFormConstraintsNoDataClassSet(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $dataClass = null;

        $this->form
            ->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formConfig
            ->expects(self::once())
            ->method('getDataClass')
            ->willReturn($dataClass);

        $this->constraints
            ->expects(self::never())
            ->method('getFormConstraints');

        $this->expectException(FormExtendedDataClassNotSetException::class);
        $object->getConstraints();
    }
}
