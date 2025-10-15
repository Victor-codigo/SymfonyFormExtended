<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Factory;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedConstraints;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedCsrfToken;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedFields;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedMessages;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedUpload;
use VictorCodigo\UploadFile\Adapter\UploadFileService;

class FormExtendedFactory
{
    private CsrfTokenManagerInterface $csrfTokenManager;
    private ValidatorInterface $validator;
    private TranslatorInterface $translator;
    private UploadFileService $uploadFile;
    private FlashBagInterface $flashBag;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        UploadFileService $uploadFile,
        RequestStack $request,
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->uploadFile = $uploadFile;

        $session = $request->getSession();

        if (!$session instanceof Session) {
            throw new \LogicException('FormFactoryExtended needs to have a session available.');
        }

        $this->flashBag = $session->getFlashBag();
    }

    /**
     * @param FormInterface<Form> $form
     */
    public function createCsrfToken(FormInterface $form): FormExtendedCsrfToken
    {
        return new FormExtendedCsrfToken($form, $this->csrfTokenManager);
    }

    public function createConstraints(): FormExtendedConstraints
    {
        return new FormExtendedConstraints($this->validator);
    }

    public function createFields(): FormExtendedFields
    {
        return new FormExtendedFields();
    }

    /**
     * @param FormInterface<Form> $form
     */
    public function createMessages(FormInterface $form, string $translationDomain, ?string $locale): FormExtendedMessages
    {
        return new FormExtendedMessages($form, $this->translator, $this->flashBag, $translationDomain, $locale);
    }

    /**
     * @param FormInterface<Form> $form
     */
    public function createUpload(FormInterface $form): FormExtendedUpload
    {
        return new FormExtendedUpload($form, $this->uploadFile);
    }
}
