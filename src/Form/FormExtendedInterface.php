<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;

/**
 * @extends FormInterface<FormExtendedInterface>
 */
interface FormExtendedInterface extends FormInterface
{
    /**
     * @return FormErrorIterator<FormError>
     */
    public function getErrorsTranslated(bool $deep = false, bool $flatten = true): FormErrorIterator;

    /**
     * @return Collection<int, string>
     */
    public function getMessagesSuccessTranslated(): Collection;

    public function addFlashMessagesTranslated(string $messagesSuccessType, string $messagesErrorType, bool $deep): void;

    /**
     * @param array<int, string|null> $fileNamesToReplace Name of files that should be replaced by the new ones.$uploadedFiles and $fileNamesToReplace keys, must coincide
     */
    public function setUploadedFilesConfig(string $pathToSaveFile, array $fileNamesToReplace = []): static;
}
