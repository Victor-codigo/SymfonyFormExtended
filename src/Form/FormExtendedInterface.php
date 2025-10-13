<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use VictorCodigo\SymfonyFormExtended\Form\Exception\FormExtendedCsrfTokenNotSetException;
use VictorCodigo\SymfonyFormExtended\Form\Exception\FormExtendedDataClassNotSetException;

/**
 * @extends FormInterface<FormExtendedInterface>
 */
interface FormExtendedInterface extends FormInterface
{
    /**
     * @return Collection<int, FormMessage>
     */
    public function getMessageErrorsTranslated(bool $deep = false, bool $flatten = true): Collection;

    /**
     * @return Collection<int, FormMessage>
     */
    public function getMessagesSuccessTranslated(): Collection;

    public function addFlashMessagesTranslated(string $messagesSuccessType, string $messagesErrorType, bool $deep): void;

    /**
     * @param array<int, string> $filenamesToBeReplacedByUploaded
     */
    public function uploadFiles(Request $request, string $pathToSaveUploadedFiles, array $filenamesToBeReplacedByUploaded = []): static;

    /**
     * @return Collection<int, FormMessage>
     */
    public function getFlashMessagesData(string $messagesType): Collection;

    /**
     * @return Collection<int, string>
     */
    public function getFlashMessages(string $messagesType): Collection;

    /**
     * @throws FormExtendedDataClassNotSetException
     */
    public function getConstraints(): object;

    /**
     * @param array<int, \BackedEnum> $formFields
     */
    public function fieldsToObject(array $formFields): object;

    /**
     * @throws FormExtendedCsrfTokenNotSetException
     */
    public function getCsrfToken(): string;
}
