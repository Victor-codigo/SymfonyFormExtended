<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
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
            ])
            ->getMock();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
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
    }

    private function createFormExtendedFactory(bool $mockMethods): FormExtendedFactory&MockObject
    {
        $formExtendedFactory = $this
            ->getMockBuilder(FormExtendedFactory::class)
            ->setConstructorArgs([
                $this->csrfTokenManager,
                $this->validator,
                $this->translator,
                $this->flashBag,
                $this->uploadFile,
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
}
