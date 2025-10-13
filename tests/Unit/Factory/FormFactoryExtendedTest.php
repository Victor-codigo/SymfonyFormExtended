<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Factory;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Factory\FormFactoryExtended;
use VictorCodigo\SymfonyFormExtended\Form\FormExtended;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedConstraints;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedCsrfToken;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedFields;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait\TestingFormTrait as TraitTestingFormTrait;
use VictorCodigo\UploadFile\Adapter\UploadFileService;

class FormFactoryExtendedTest extends TestCase
{
    use TraitTestingFormTrait;

    private FormFactoryInterface&MockObject $formFactory;
    private FormRegistryInterface&MockObject $formRegistry;
    /**
     * @var FormInterface<mixed>&MockObject
     */
    private FormInterface&MockObject $form;
    /**
     * @var FormConfigInterface<object>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private TranslatorInterface&MockObject $translator;
    private RequestStack&MockObject $request;
    private FlashBagInterface&MockObject $flashBag;
    private UploadFileService&MockObject $uploadedFile;
    private FormExtendedConstraints&MockObject $constraints;
    private FormExtendedFields&MockObject $formFields;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;
    /**
     * @var FormBuilderInterface<object>&MockObject
     */
    private FormBuilderInterface&MockObject $formBuilder;
    private FormExtendedCsrfToken&MockObject $formExtendedCsrfToken;
    private Session&MockObject $session;
    private FormTypeForTesting $formType;
    private string $locale = 'locale';

    protected function setUp(): void
    {
        parent::setUp();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->formRegistry = $this->createMock(FormRegistryInterface::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->request = $this->createMock(RequestStack::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->uploadedFile = $this->createMock(UploadFileService::class);
        $this->constraints = $this->createMock(FormExtendedConstraints::class);
        $this->formFields = $this->createMock(FormExtendedFields::class);
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->formExtendedCsrfToken = $this->createMock(FormExtendedCsrfToken::class);
        $this->formType = new FormTypeForTesting($this->translator);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
    }

    /**
     * @return FormFactoryExtended<mixed>
     */
    private function createFormFactorExtended(): FormFactoryExtended
    {
        return new FormFactoryExtended(
            $this->formFactory,
            $this->translator,
            $this->uploadedFile,
            $this->constraints,
            $this->formFields,
            $this->formExtendedCsrfToken,
            $this->request
        );
    }

    #[Test]
    public function itShouldCreateAFormExtended(): void
    {
        $formName = 'formName';
        $formType = FormTypeForTesting::class;

        $this->request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($this->session);

        $this->formFactory
            ->expects($this->once())
            ->method('createNamedBuilder')
            ->willReturn($this->formBuilder);

        $this->form
            ->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formRegistry
            ->expects($this->any())
            ->method('getType')
            ->with(FormTypeForTesting::class)
            ->willReturn($this->resolvedFormType);

        $this->formBuilder
            ->expects($this->once())
            ->method('getForm')
            ->willReturn($this->form);

        $this->session
            ->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($this->flashBag);

        $formExpected = new FormExtended(
            $this->form,
            $this->translator,
            $this->flashBag,
            $this->uploadedFile,
            $this->constraints,
            $this->formFields,
            $this->formExtendedCsrfToken,
            $this->locale
        );
        $object = $this->createFormFactorExtended();

        $return = $object->createNamedExtended($formName, $formType, $this->locale);

        self::assertInstanceOf(FormExtended::class, $return);
        self::assertEquals($formExpected->getName(), $return->getName());
        self::assertEquals($formExpected->translationDomain, $return->translationDomain);
        self::assertEquals($formExpected->locale, $return->locale);
    }

    #[Test]
    public function itShouldFailCreatingAFormExtendedSessionNotStarted(): void
    {
        $sessionNotStarted = $this->createMock(SessionInterface::class);

        $this->request
            ->expects($this->once())
            ->method('getSession')
            ->willReturn($sessionNotStarted);

        $this->formFactory
            ->expects($this->never())
            ->method('createNamedBuilder');

        $this->form
            ->expects($this->never())
            ->method('getConfig');

        $this->formRegistry
            ->expects($this->never())
            ->method('getType');

        $this->formBuilder
            ->expects($this->never())
            ->method('getForm');

        $this->session
            ->expects($this->never())
            ->method('getFlashBag');

        $this->expectException(\LogicException::class);
        $this->createFormFactorExtended();
    }
}
