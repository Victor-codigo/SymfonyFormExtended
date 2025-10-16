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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Factory\FormExtendedFactory;
use VictorCodigo\SymfonyFormExtended\Form\Exception\FormExtendedDataClassNotSetException;
use VictorCodigo\SymfonyFormExtended\Form\FormExtended;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedConstraints;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedCsrfToken;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedFields;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedMessages;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedUpload;
use VictorCodigo\SymfonyFormExtended\Form\FormMessage;
use VictorCodigo\SymfonyFormExtended\Form\FormTemplateData;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormDataClassForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormFieldsEnum;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait\TestingFormTrait;
use VictorCodigo\UploadFile\Adapter\UploadFileService;

class FormExtendedTest extends TestCase
{
    use TestingFormTrait;

    /**
     * @var FormInterface<Form>|MockObject
     */
    private FormInterface&MockObject $form;
    /**
     * @var FormConfigInterface<object>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private TranslatorInterface&MockObject $translator;
    private ValidatorInterface&MockObject $validator;
    private FlashBagInterface&MockObject $flashBag;
    private SessionInterface&MockObject $session;
    private RequestStack&MockObject $request;
    private UploadFileService&MockObject $uploadFile;
    private CsrfTokenManagerInterface&MockObject $csrfTokenManager;
    private FormExtendedCsrfToken&MockObject $formExtendedCsrfToken;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;
    private FormExtendedConstraints&MockObject $formExtendedConstraints;
    private FormExtendedFields&MockObject $formExtendedFields;
    private FormExtendedUpload&MockObject $formExtendedUploaded;
    private FormExtendedMessages&MockObject $formExtendedMessages;
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
                'isSubmitted',
                'isValid',
            ])
            ->getMock();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->uploadFile = $this->createMock(UploadFileService::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->formExtendedConstraints = $this->createMock(FormExtendedConstraints::class);
        $this->formExtendedFields = $this->createMock(FormExtendedFields::class);
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $this->formExtendedCsrfToken = $this->createMock(FormExtendedCsrfToken::class);
        $this->formExtendedUploaded = $this->createMock(FormExtendedUpload::class);
        $this->formExtendedMessages = $this->createMock(FormExtendedMessages::class);
        $this->formType = new FormTypeForTesting($this->translator);

        $this->request = $this->createMockRequest($this->session);
    }

    private function createFormExtendedFactory(bool $mockMethods): FormExtendedFactory&MockObject
    {
        $formExtendedFactory = $this
            ->getMockBuilder(FormExtendedFactory::class)
            ->setConstructorArgs([
                $this->csrfTokenManager,
                $this->validator,
                $this->translator,
                $this->uploadFile,
                $this->request,
            ])
            ->onlyMethods([
                'createCsrfToken',
                'createConstraints',
                'createFields',
                'createMessages',
                'createUpload',
            ])
            ->getMock();

        if (!$mockMethods) {
            return $formExtendedFactory;
        }

        $formExtendedFactory
            ->expects(self::once())
            ->method('createCsrfToken')
            ->willReturn($this->formExtendedCsrfToken);

        $formExtendedFactory
            ->expects(self::once())
            ->method('createConstraints')
            ->willReturn($this->formExtendedConstraints);

        $formExtendedFactory
            ->expects(self::once())
            ->method('createFields')
            ->willReturn($this->formExtendedFields);

        $formExtendedFactory
            ->expects(self::once())
            ->method('createMessages')
            ->willReturn($this->formExtendedMessages);

        $formExtendedFactory
            ->expects(self::once())
            ->method('createUpload')
            ->willReturn($this->formExtendedUploaded);

        return $formExtendedFactory;
    }

    private function createMockRequest(Session&MockObject $session): RequestStack&MockObject
    {
        $request = $this->createMock(RequestStack::class);

        $request
            ->expects(self::any())
            ->method('getSession')
            ->willReturn($session);

        $session
            ->expects(self::any())
            ->method('getFlashBag')
            ->willReturn($this->flashBag);

        return $request;
    }

    private function createFormExtended(bool $mockMethods): FormExtended
    {
        $formExtendedFactory = $this->createFormExtendedFactory($mockMethods);

        return new FormExtended($this->form, $formExtendedFactory, $this->locale);
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
        $this->createFormExtended(false);
    }

    #[Test]
    public function itShouldGetTheIterator(): void
    {
        $iterator = new \ArrayIterator();
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended(true);

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
        $object = $this->createFormExtended(true);
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
        $object = $this->createFormExtended(true);

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
        $object = $this->createFormExtended(true);
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
        $object = $this->createFormExtended(true);
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

        $this->formExtendedConstraints
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
        $object = $this->createFormExtended(true);
        $dataClass = null;

        $this->form
            ->expects(self::exactly(2))
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formConfig
            ->expects(self::once())
            ->method('getDataClass')
            ->willReturn($dataClass);

        $this->formExtendedConstraints
            ->expects(self::never())
            ->method('getFormConstraints');

        $this->expectException(FormExtendedDataClassNotSetException::class);
        $object->getConstraints();
    }

    #[Test]
    public function itShouldCreateAClassFormTemplateData(): void
    {
        $formName = 'form_name';
        $formCsrfToken = 'CSRF token';
        $formIsValidForm = true;
        $formDataClass = FormDataClassForTesting::class;
        $formFields = new class {
            public string $field_1 = 'field_1';
            public string $field_2 = 'field_2';
            public string $field_3 = 'field_3';
            public string $field_4 = 'field_4[]';
            public string $field_5 = 'field_5[]';
        };
        $formConstraints = new class {
            public string $name = 'name';
            public int $int = 8;
        };
        $formMessagesError = new ArrayCollection([
            new FormMessage(
                'message error 1',
                'message error 1 template',
                ['message error 1 parameters'],
                null
            ),
            new FormMessage(
                'message error 2',
                'message error 2 template',
                ['message error 2 parameters'],
                null
            ),
        ]);
        $formMessagesOk = new ArrayCollection([
            new FormMessage(
                'message ok 1',
                'message ok 1 template',
                ['message ok 1 parameters'],
                null
            ),
            new FormMessage(
                'message ok 2',
                'message ok 2 template',
                ['message ok 2 parameters'],
                null
            ),
        ]);

        $messagesError = [
            'message error 1',
            'message error 2',
        ];
        $messagesOk = [
            'message ok 1',
            'message ok 2',
        ];

        $formTemplateDataExpected = new FormTemplateData(
            $formFields,
            $formConstraints,
            $formCsrfToken,
            $formIsValidForm,
            $messagesError,
            $messagesOk
        );

        $this->formExtendedFields
            ->expects(self::once())
            ->method('generateAnObjectWithFormNameAndFields')
            ->with($formName, FormFieldsEnum::cases())
            ->willReturn($formFields);

        $this->formExtendedConstraints
            ->expects(self::once())
            ->method('getFormConstraints')
            ->willReturn($formConstraints);

        $this->formExtendedCsrfToken
            ->expects(self::once())
            ->method('getCsrfToken')
            ->willReturn($formCsrfToken);

        $this->form
            ->expects(self::once())
            ->method('isSubmitted')
            ->willReturn($formIsValidForm);

        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn($formIsValidForm);

        $this->form
            ->expects(self::once())
            ->method('getName')
            ->willReturn($formName);

        $this->form
            ->expects(self::any())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formConfig
            ->expects(self::once())
            ->method('getDataClass')
            ->willReturn($formDataClass);

        $this->formExtendedMessages
            ->expects(self::once())
            ->method('getMessageErrorsTranslated')
            ->with(false, true)
            ->willReturn($formMessagesError);

        $this->formExtendedMessages
            ->expects(self::once())
            ->method('getMessagesSuccessTranslated')
            ->willReturn($formMessagesOk);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended(true);

        $return = $object->getFormTemplateData(FormFieldsEnum::cases());

        self::assertEquals($formTemplateDataExpected, $return);
    }
}
