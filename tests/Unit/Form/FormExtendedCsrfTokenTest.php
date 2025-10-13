<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use VictorCodigo\SymfonyFormExtended\Form\Exception\FormExtendedCsrfTokenNotSetException;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedCsrfToken;

class FormExtendedCsrfTokenTest extends TestCase
{
    private const string ID_OF_FORM_CSRF_TOKEN_ID = 'csrf_token_id';

    private FormExtendedCsrfToken $object;
    /**
     * @var FormInterface<object>|MockObject
     */
    private FormInterface&MockObject $form;
    private CsrfTokenManagerInterface&MockObject $csrfTokenManager;
    /**
     * @var FormConfigInterface<object>|MockObject
     */
    private FormConfigInterface&MockObject $formConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form = $this->createMock(FormInterface::class);
        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->object = new FormExtendedCsrfToken(
            $this->form,
            $this->csrfTokenManager
        );
    }

    #[Test]
    public function itShouldGetCsrfToken(): void
    {
        $tokenExpected = new CsrfToken(self::ID_OF_FORM_CSRF_TOKEN_ID, 'csrf token');
        $this->form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with(self::ID_OF_FORM_CSRF_TOKEN_ID)
            ->willReturn(self::ID_OF_FORM_CSRF_TOKEN_ID);

        $this->csrfTokenManager
            ->expects(self::once())
            ->method('getToken')
            ->with(self::ID_OF_FORM_CSRF_TOKEN_ID)
            ->willReturn($tokenExpected);

        $return = $this->object->getCsrfToken();

        self::assertEquals($tokenExpected->getValue(), $return);
    }

    #[Test]
    public function itShouldFailGettingCsrfTokenIdNotExists(): void
    {
        $idTokenExpected = null;
        $this->form
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $this->formConfig
            ->expects(self::once())
            ->method('getOption')
            ->with(self::ID_OF_FORM_CSRF_TOKEN_ID)
            ->willReturn($idTokenExpected);

        $this->csrfTokenManager
            ->expects(self::never())
            ->method('getToken');

        $this->expectException(FormExtendedCsrfTokenNotSetException::class);
        $this->object->getCsrfToken();
    }
}
