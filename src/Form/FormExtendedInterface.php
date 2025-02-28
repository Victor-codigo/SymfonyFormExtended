<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

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
}
