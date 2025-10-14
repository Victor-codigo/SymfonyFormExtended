<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FormSizeFileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use VictorCodigo\UploadFile\Adapter\UploadFileService;
use VictorCodigo\UploadFile\Adapter\UploadedFileSymfonyAdapter;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadCanNotWriteException;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadException;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadExtensionFileException;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadIniSizeException;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadNoFileException;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadPartialFileException;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadReplaceException;
use VictorCodigo\UploadFile\Domain\Exception\FileUploadTmpDirFileException;
use VictorCodigo\UploadFile\Domain\FileInterface;
use VictorCodigo\UploadFile\Domain\UploadedFileInterface;

class FormExtendedUpload
{
    /**
     * @param FormInterface<Form> $form
     */
    public function __construct(
        private FormInterface $form,
        private UploadFileService $uploadFile,
    ) {
    }

    /**
     * @param array<int, string> $filenamesToBeReplacedByUploaded
     */
    public function uploadFiles(Request $request, string $pathToSaveUploadedFiles, array $filenamesToBeReplacedByUploaded = []): static
    {
        $uploadedFilesMovedToPath = $this->uploadFormFiles($request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        $this->setDataClassFiles($uploadedFilesMovedToPath);

        return $this;
    }

    /**
     * @param array<int, string> $filenamesToBeReplacedByUploaded
     *
     * @return array<string, FileInterface>
     *
     * @throws FileUploadCanNotWriteException
     * @throws FileUploadExtensionFileException
     * @throws FileUploadException
     * @throws FormSizeFileException
     * @throws FileUploadIniSizeException
     * @throws FileUploadNoFileException
     * @throws FileUploadTmpDirFileException
     * @throws FileUploadPartialFileException
     * @throws FileUploadReplaceException
     */
    private function uploadFormFiles(Request $request, string $pathToSaveUploadedFiles, array $filenamesToBeReplacedByUploaded): array
    {
        $uploadedFiles = $this->getUploadedFormFiles($request);
        $uploadedFilesMovedToPath = [];

        /** @var string|false */
        $fileNameToReplace = reset($filenamesToBeReplacedByUploaded);
        foreach ($uploadedFiles as $propertyName => $uploadFile) {
            if (false === $fileNameToReplace) {
                $fileNameToReplace = null;
            }

            $uploadedFilesMovedToPath[$propertyName] = $this->uploadFile->__invoke($uploadFile, $pathToSaveUploadedFiles, $fileNameToReplace);

            /** @var string|false */
            $fileNameToReplace = next($filenamesToBeReplacedByUploaded);
        }

        return $uploadedFilesMovedToPath;
    }

    /**
     * @return array<string, UploadedFileInterface>
     */
    private function getUploadedFormFiles(Request $request): array
    {
        /** @var array<string, UploadedFile|null> */
        $uploadedFiles = $request->files->all($this->form->getName());

        $uploadedFilesParsed = [];
        foreach ($uploadedFiles as $fileName => $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $uploadedFilesParsed[$fileName] = new UploadedFileSymfonyAdapter($uploadedFile);
        }

        return $uploadedFilesParsed;
    }

    /**
     * @param array<string, FileInterface> $imagesUploaded
     */
    private function setDataClassFiles(array $imagesUploaded): void
    {
        if (empty($imagesUploaded)) {
            return;
        }

        /** @var object */
        $formDataClass = $this->form->getData();

        foreach ($imagesUploaded as $propertyName => $file) {
            if (!property_exists($formDataClass, $propertyName)) {
                continue;
            }

            $formDataClass->$propertyName = $file->getFile();
        }
    }
}
