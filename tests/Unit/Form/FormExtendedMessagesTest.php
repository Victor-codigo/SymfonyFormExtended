<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedMessages;
use VictorCodigo\SymfonyFormExtended\Form\FormMessage;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait\TestingFormTrait;

class FormExtendedMessagesTest extends TestCase
{
    use TestingFormTrait;

    private FormExtendedMessages $object;

    /**
     * @var FormInterface<mixed>|MockObject
     */
    private FormInterface&MockObject $form;
    private TranslatorInterface&MockObject $translator;
    private FlashBagInterface&MockObject $flashBag;
    /**
     * @var FormConfigInterface<object>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private FormTypeForTesting $formType;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;

    public string $translationDomain = FormTypeForTesting::TRANSLATION_DOMAIN;
    public ?string $locale = 'locale';

    protected function setUp(): void
    {
        parent::setUp();

        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->formType = new FormTypeForTesting($this->translator);
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
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

        $this->object = new FormExtendedMessages(
            $this->form,
            $this->translator,
            $this->flashBag,
            $this->translationDomain,
            $this->locale
        );
    }

    #[Test]
    public function itShouldGetErrorsTranslatedDeepAndFlattenAreTrue(): void
    {
        $deep = true;
        $flatten = true;
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageErrors): FormInterface => $this->form->addError(new FormError(
            $messageErrors->message,
            $messageErrors->template,
            $messageErrors->parameters,
            $messageErrors->pluralization,
            $this->object
        )));
        $messageErrorsTranslated = $this->getMessageErrorsTranslated(
            new ArrayCollection(iterator_to_array($this->object->getMessageErrorsTranslated(true))),
            $this->form
        );
        $this->createSubFormMethodTrans($messageErrors, $messageErrorsTranslated);

        $return = $this->object->getMessageErrorsTranslated($deep, $flatten);

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
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageError): FormInterface => $this->form->addError(new FormError(
            $messageError->message,
            $messageError->template,
            $messageError->parameters,
            $messageError->pluralization
        )));
        $messageErrorsTranslated = $this->getMessageErrorsTranslated(
            new ArrayCollection(iterator_to_array($this->object->getMessageErrorsTranslated(true))),
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

        $return = $this->object->getMessageErrorsTranslated($deep, $flatten);

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

        $return = $this->object->getMessageErrorsTranslated($deep, $flatten);

        self::assertCount(0, $return);
    }

    #[Test]
    public function itShouldGetMessagesOkTranslated(): void
    {
        $messagesOk = FormTypeForTesting::getFormMessageErrors();
        $messageErrorsTranslated = $this->getMessageErrorsTranslated($messagesOk, $this->form);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $this->createSubFormMethodTrans($messagesOk, $messageErrorsTranslated);

        $return = $this->object->getMessagesSuccessTranslated();

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

        $this->object->addFlashMessagesTranslated($flashBagSuccessType, $flashBagErrorType, true);
    }

    #[Test]
    public function itShouldAddErrorFormFlashMessages(): void
    {
        $flashBagSuccessType = 'success';
        $flashBagErrorType = 'error';
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageError): FormInterface => $this->form->addError(new FormError(
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

        $this->object->addFlashMessagesTranslated($flashBagSuccessType, $flashBagErrorType, true);
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

        $this->flashBag
            ->expects($this->once())
            ->method('get')
            ->with($messagesType)
            ->willReturn($messagesData->toArray());

        $return = $this->object->getFlashMessagesData($messagesType);

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

        $this->flashBag
            ->expects($this->once())
            ->method('get')
            ->with($messagesType)
            ->willReturn($messagesData->toArray());

        $return = $this->object->getFlashMessages($messagesType);

        self::assertEquals(
            $messagesData->map(fn (FormMessage $message): string => $message->message),
            $return,
        );
    }
}
