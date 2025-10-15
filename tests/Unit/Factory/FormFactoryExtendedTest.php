<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Factory;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Factory\FormExtendedFactory;
use VictorCodigo\SymfonyFormExtended\Factory\FormFactoryExtended;
use VictorCodigo\SymfonyFormExtended\Form\FormExtended;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait\TestingFormTrait as TraitTestingFormTrait;

class FormFactoryExtendedTest extends TestCase
{
    use TraitTestingFormTrait;

    private FormFactoryInterface&MockObject $formFactory;
    private FormRegistryInterface&MockObject $formRegistry;
    /**
     * @var FormInterface<Form>&MockObject
     */
    private FormInterface&MockObject $form;
    /**
     * @var FormConfigInterface<object>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private TranslatorInterface&MockObject $translator;
    private RequestStack&MockObject $request;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;
    /**
     * @var FormBuilderInterface<object>&MockObject
     */
    private FormBuilderInterface&MockObject $formBuilder;
    private FormExtendedFactory&MockObject $formExtendedFactory;
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
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->formExtendedFactory = $this->createMock(FormExtendedFactory::class);
        $this->formType = new FormTypeForTesting($this->translator);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
    }

    /**
     * @return FormFactoryExtended<mixed>
     */
    private function createFormFactoryExtended(): FormFactoryExtended
    {
        return new FormFactoryExtended(
            $this->formFactory,
            $this->formExtendedFactory,
            $this->request
        );
    }

    #[Test]
    public function itShouldCreateAFormExtended(): void
    {
        $formName = 'formName';
        $formType = FormTypeForTesting::class;

        $this->request
            ->expects(self::once())
            ->method('getSession')
            ->willReturn($this->session);

        $this->formFactory
            ->expects(self::once())
            ->method('createNamedBuilder')
            ->willReturn($this->formBuilder);

        $this->form
            ->expects(self::any())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formRegistry
            ->expects(self::any())
            ->method('getType')
            ->with(FormTypeForTesting::class)
            ->willReturn($this->resolvedFormType);

        $this->formBuilder
            ->expects(self::once())
            ->method('getForm')
            ->willReturn($this->form);

        $formExpected = new FormExtended($this->form, $this->formExtendedFactory, $this->locale);
        $object = $this->createFormFactoryExtended();

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
            ->expects(self::never())
            ->method('createNamedBuilder');

        $this->form
            ->expects(self::never())
            ->method('getConfig');

        $this->formRegistry
            ->expects(self::never())
            ->method('getType');

        $this->formBuilder
            ->expects(self::never())
            ->method('getForm');

        $this->expectException(\LogicException::class);
        $this->createFormFactoryExtended();
    }
}
