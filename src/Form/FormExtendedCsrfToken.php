<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use VictorCodigo\SymfonyFormExtended\Form\Exception\FormExtendedCsrfTokenNotSetException;

class FormExtendedCsrfToken
{
    private const string ID_OF_FORM_CSRF_TOKEN_ID = 'csrf_token_id';

    /**
     * @param FormInterface<mixed> $form
     */
    public function __construct(
        private FormInterface $form,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    /**
     * @throws FormExtendedCsrfTokenNotSetException
     */
    public function getCsrfToken(): string
    {
        /** @var string|null */
        $csrfTokenId = $this->form->getConfig()->getOption(self::ID_OF_FORM_CSRF_TOKEN_ID);

        if (null === $csrfTokenId) {
            throw FormExtendedCsrfTokenNotSetException::fromMessage('Csrf token id not found');
        }

        return $this->csrfTokenManager->getToken($csrfTokenId)->getValue();
    }
}
