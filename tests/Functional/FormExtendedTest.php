<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Factory\FormExtendedFactory;
use VictorCodigo\SymfonyFormExtended\Factory\FormFactoryExtended;
use VictorCodigo\SymfonyFormExtended\Factory\FormFactoryExtendedInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormExtended;
use VictorCodigo\SymfonyFormExtended\Tests\Functional\Fixture\FormTypeForTesting;
use VictorCodigo\UploadFile\Adapter\UploadFileService;

class FormExtendedTest extends TestCase
{
    /**
     * @var FormFactoryExtended<object>
     */
    private FormFactoryExtendedInterface $formFactoryExtended;
    private FormExtendedFactory $formExtendedFactory;
    private FormFactoryInterface&MockObject $formFactory;
    /**
     * @var FormBuilderInterface<FormBuilder>&MockObject
     */
    private FormBuilderInterface&MockObject $formBuilder;
    /**
     * @var FormInterface<Form>&MockObject
     */
    private FormInterface&MockObject $form;
    /**
     * @var FormConfigInterface<Form>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;
    private CsrfTokenManagerInterface&MockObject $csrfTokenManager;
    private ValidatorInterface&MockObject $validator;
    private TranslatorInterface&MockObject $translator;
    private FlashBagInterface&MockObject $flashBag;
    private UploadFileService&MockObject $uploadFile;
    private RequestStack&MockObject $request;
    private Session&MockObject $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formBuilder = $this->createMock(FormBuilderInterface::class);

        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->uploadFile = $this->createMock(UploadFileService::class);
        $this->session = $this->createMock(Session::class);

        $this->request = $this->createMockRequest($this->session);
        $this->form = $this->createMockForm($this->formConfig, $this->resolvedFormType, $this->translator);
        $this->formFactory = $this->createMockFormFactory($this->formBuilder, $this->form);

        $this->formExtendedFactory = new FormExtendedFactory(
            $this->csrfTokenManager,
            $this->validator,
            $this->translator,
            $this->flashBag,
            $this->uploadFile
        );
        $this->formFactoryExtended = new FormFactoryExtended(
            $this->formFactory,
            $this->formExtendedFactory,
            $this->request
        );
    }

    /**
     * @param FormBuilderInterface<FormBuilder>&MockObject $formBuilder
     * @param FormInterface<Form>&MockObject               $form
     */
    private function createMockFormFactory(FormBuilderInterface&MockObject $formBuilder, FormInterface&MockObject $form): FormFactoryInterface&MockObject
    {
        $formFactory = $this->createMock(FormFactoryInterface::class);

        $formFactory
            ->expects(self::once())
            ->method('createNamedBuilder')
            ->willReturn($formBuilder);

        $formBuilder
            ->expects(self::once())
            ->method('getForm')
            ->willReturn($form);

        return $formFactory;
    }

    private function createMockRequest(Session&MockObject $session): RequestStack&MockObject
    {
        $request = $this->createMock(RequestStack::class);

        $request
            ->expects(self::once())
            ->method('getSession')
            ->willReturn($session);

        return $request;
    }

    /**
     * @param FormConfigInterface<Form>&MockObject $formConfig
     *
     * @return FormInterface<Form>&MockObject
     */
    private function createMockForm(FormConfigInterface&MockObject $formConfig, ResolvedFormTypeInterface&MockObject $resolvedFormType, TranslatorInterface&MockObject $translator): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);

        $form
            ->expects(self::any())
            ->method('getConfig')
            ->willReturn($formConfig);

        $this->formConfig
            ->expects(self::any())
            ->method('getType')
            ->willReturn($resolvedFormType);

        $this->resolvedFormType
            ->expects(self::any())
            ->method('getInnerType')
            ->willReturn(new FormTypeForTesting($translator));

        return $form;
    }

    #[Test]
    public function itShouldCreateAFormExtendedNamedForm(): void
    {
        $formName = 'form';
        $locale = 'es';
        $data = ['param1' => 'value1'];
        $options = ['option1' => 'value1'];

        $return = $this->formFactoryExtended->createNamedExtended($formName, FormTypeForTesting::class, $locale, $data, $options);

        self::assertInstanceOf(FormExtended::class, $return);
        self::assertSame($this->form, $return->form);
    }
}
