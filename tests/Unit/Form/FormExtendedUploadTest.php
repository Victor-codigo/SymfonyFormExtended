<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormExtendedUpload;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormDataClassForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait\TestingFormTrait;
use VictorCodigo\UploadFile\Adapter\UploadFileService;
use VictorCodigo\UploadFile\Adapter\UploadedFileSymfonyAdapter;
use VictorCodigo\UploadFile\Domain\UploadedFileInterface;

class FormExtendedUploadTest extends TestCase
{
    use TestingFormTrait;

    private FormExtendedUpload $object;
    private UploadFileService&MockObject $uploadFile;

    /**
     * @var FormInterface<mixed>|MockObject
     */
    private FormInterface&MockObject $form;
    /**
     * @var FormConfigInterface<object>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private TranslatorInterface&MockObject $translator;
    private FormTypeForTesting $formType;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;
    private Request&MockObject $request;
    private string $locale = 'locale';

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadFile = $this->createMock(UploadFileService::class);
        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formType = new FormTypeForTesting($this->translator);
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->form = $this
            ->getMockBuilder(Form::class)
            ->setConstructorArgs([$this->formConfig])
            ->onlyMethods([
                'getConfig',
                'handleRequest',
                'getName',
                'getData',
                'getIterator',
                'clearErrors',
            ])
            ->getMock();

        $this->object = new FormExtendedUpload(
            $this->form,
            $this->uploadFile
        );
    }

    /**
     * @param Collection<string, UploadedFile|null> $formFilesUploaded
     */
    private function setRequestFilesUploaded(string $formName, Collection $formFilesUploaded): void
    {
        $this->request->files = new FileBag();
        $this->request->files->set($formName, $formFilesUploaded->toArray());
    }

    /**
     * @param Collection<string, UploadedFileSymfonyAdapter&MockObject> $images
     */
    private function fillFormClassData(Collection $images): FormDataClassForTesting
    {
        $formDataClassExpected = new FormDataClassForTesting();
        $formDataClassExpected->image1 = $images->get('image1')?->getFile();
        $formDataClassExpected->image2 = $images->get('image2')?->getFile();
        $formDataClassExpected->image3 = $images->get('image3')?->getFile();

        return $formDataClassExpected;
    }

    #[Test]
    public function itShouldUploadFiles(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $formFilesUploadedSymfonyAdapter = $this->getFormUploadedFilesMock();
        $formFilesUploadedMovedToNewPath = $this->getFormUploadedFilesMock();
        /** @var Collection<string, UploadedFile|null> */
        $formFilesUploaded = $formFilesUploadedSymfonyAdapter->map(fn (UploadedFileSymfonyAdapter&MockObject $uploadedFile): UploadedFile => $uploadedFile->getFile());
        $this->setRequestFilesUploaded($formName, $formFilesUploaded);
        $formDataClass = new FormDataClassForTesting();
        $formDataClassExpected = $this->fillFormClassData($formFilesUploadedMovedToNewPath);

        $this->form
            ->expects($this->once())
            ->method('getName')
            ->willReturn($formName);

        $this->form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($formDataClass);

        $__invokeInvokeCalls = $this->exactly($formFilesUploaded->count());
        $this->uploadFile
            ->expects($__invokeInvokeCalls)
            ->method('__invoke')
            ->with(self::callback(function (UploadedFileInterface $uploadedFile) use ($__invokeInvokeCalls, $formFilesUploaded): bool {
                match ($__invokeInvokeCalls->numberOfInvocations()) {
                    1 => self::assertEquals($formFilesUploaded->get('image1'), $uploadedFile->getFile()),
                    2 => self::assertEquals($formFilesUploaded->get('image2'), $uploadedFile->getFile()),
                    3 => self::assertEquals($formFilesUploaded->get('image3'), $uploadedFile->getFile()),
                    default => throw new \LogicException('There is only 3 files uploaded'),
                };

                return true;
            }),
                $pathToSaveUploadedFiles,
                null
            )
            ->willReturnOnConsecutiveCalls(
                $formFilesUploadedMovedToNewPath->get('image1'),
                $formFilesUploadedMovedToNewPath->get('image2'),
                $formFilesUploadedMovedToNewPath->get('image3'),
            );

        $return = $this->object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($this->object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldNotUploadFilesRequestHasNot(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $formFilesUploaded = new ArrayCollection();
        $formDataClass = new FormDataClassForTesting();
        $formDataClassExpected = new FormDataClassForTesting();
        $this->setRequestFilesUploaded($formName, $formFilesUploaded);

        $this->form
            ->expects($this->once())
            ->method('getName')
            ->willReturn($formName);

        $this->form
            ->expects($this->never())
            ->method('getData');

        $this->uploadFile
            ->expects($this->never())
            ->method('__invoke');

        $return = $this->object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($this->object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldNotUploadFilesRequestImageIsNull(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        /** @var Collection<string, UploadedFile|null> */
        $formFilesUploaded = new ArrayCollection(['image' => null]);
        $formDataClass = new FormDataClassForTesting();
        $formDataClassExpected = new FormDataClassForTesting();
        $this->setRequestFilesUploaded($formName, $formFilesUploaded);

        $this->form
            ->expects($this->once())
            ->method('getName')
            ->willReturn($formName);

        $this->form
            ->expects($this->never())
            ->method('getData');

        $this->uploadFile
            ->expects($this->never())
            ->method('__invoke');

        $return = $this->object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($this->object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldUploadFilesAndFilesToReplace(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [
            'fileToReplace1',
            'fileToReplace2',
        ];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $formFilesUploadedSymfonyAdapter = $this->getFormUploadedFilesMock();
        $formFilesUploadedMovedToNewPath = $this->getFormUploadedFilesMock();
        /** @var Collection<string, UploadedFile|null> */
        $formFilesUploaded = $formFilesUploadedSymfonyAdapter->map(fn (UploadedFileSymfonyAdapter&MockObject $uploadedFile): UploadedFile => $uploadedFile->getFile());
        $this->setRequestFilesUploaded($formName, $formFilesUploaded);
        $formDataClass = new FormDataClassForTesting();
        $formDataClassExpected = $this->fillFormClassData($formFilesUploadedMovedToNewPath);

        $this->form
            ->expects($this->once())
            ->method('getName')
            ->willReturn($formName);

        $this->form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($formDataClass);

        $__invokeInvokeCalls = $this->exactly($formFilesUploaded->count());
        $this->uploadFile
            ->expects($__invokeInvokeCalls)
            ->method('__invoke')
            ->with(self::callback(function (UploadedFileInterface $uploadedFile) use ($__invokeInvokeCalls, $formFilesUploaded): bool {
                match ($__invokeInvokeCalls->numberOfInvocations()) {
                    1 => self::assertEquals($formFilesUploaded->get('image1'), $uploadedFile->getFile()),
                    2 => self::assertEquals($formFilesUploaded->get('image2'), $uploadedFile->getFile()),
                    3 => self::assertEquals($formFilesUploaded->get('image3'), $uploadedFile->getFile()),
                    default => throw new \LogicException('There is only 3 files uploaded'),
                };

                return true;
            }),
                $pathToSaveUploadedFiles,
                self::callback(function (?string $fileNameToReplace) use ($__invokeInvokeCalls, $filenamesToBeReplacedByUploaded): bool {
                    match ($__invokeInvokeCalls->numberOfInvocations()) {
                        1 => self::assertEquals($filenamesToBeReplacedByUploaded[0], $fileNameToReplace),
                        2 => self::assertEquals($filenamesToBeReplacedByUploaded[1], $fileNameToReplace),
                        3 => self::assertNull($fileNameToReplace),
                        default => throw new \LogicException('There are only 2 files to replace'),
                    };

                    return true;
                })
            )
            ->willReturnOnConsecutiveCalls(
                $formFilesUploadedMovedToNewPath->get('image1'),
                $formFilesUploadedMovedToNewPath->get('image2'),
                $formFilesUploadedMovedToNewPath->get('image3'),
            );

        $return = $this->object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($this->object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldNotUploadFilesAndFilesToReplace(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [
            'fileToReplace1',
            'fileToReplace2',
        ];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $formFilesUploaded = new ArrayCollection();
        $formDataClass = new FormDataClassForTesting();
        $formDataClassExpected = new FormDataClassForTesting();
        $this->setRequestFilesUploaded($formName, $formFilesUploaded);

        $this->form
            ->expects($this->once())
            ->method('getName')
            ->willReturn($formName);

        $this->form
            ->expects($this->never())
            ->method('getData');

        $this->uploadFile
            ->expects($this->never())
            ->method('__invoke');

        $return = $this->object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($this->object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldUploadFilesWithFilesFileNameUploadedDoesNotExistsInTheDataClass(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $formFilesUploadedSymfonyAdapter = $this->getFormUploadedFilesMock();
        $image1 = $formFilesUploadedSymfonyAdapter->get('image1') ?: throw new \LogicException('Image file 1 does not exist');
        $formFilesUploadedSymfonyAdapter->remove('image1');
        $formFilesUploadedSymfonyAdapter->set('imageNoExists', $image1);
        $formFilesUploadedMovedToNewPath = $this->getFormUploadedFilesMock();
        $formFilesUploadedMovedToNewPath->remove('image1');
        $formFilesUploadedMovedToNewPath->set('imageNoExists', $image1);
        /** @var Collection<string, UploadedFile|null> */
        $formFilesUploaded = $formFilesUploadedSymfonyAdapter->map(fn (UploadedFileSymfonyAdapter&MockObject $uploadedFile): UploadedFile => $uploadedFile->getFile());
        $this->setRequestFilesUploaded($formName, $formFilesUploaded);
        $formDataClass = new FormDataClassForTesting();
        $formDataClass->image1 = null;
        $formDataClass->image2 = null;
        $formDataClass->image3 = null;
        $formDataClassExpected = $this->fillFormClassData($formFilesUploadedMovedToNewPath);
        $formDataClassExpected->image1 = null;

        $this->form
            ->expects($this->once())
            ->method('getName')
            ->willReturn($formName);

        $this->form
            ->expects($this->once())
            ->method('getData')
            ->willReturn($formDataClass);

        $__invokeInvokeCalls = $this->exactly($formFilesUploaded->count());
        $this->uploadFile
            ->expects($__invokeInvokeCalls)
            ->method('__invoke')
            ->with(self::callback(function (UploadedFileInterface $uploadedFile) use ($__invokeInvokeCalls, $formFilesUploaded): bool {
                match ($__invokeInvokeCalls->numberOfInvocations()) {
                    1 => self::assertEquals($formFilesUploaded->get('imageNoExists'), $uploadedFile->getFile()),
                    2 => self::assertEquals($formFilesUploaded->get('image2'), $uploadedFile->getFile()),
                    3 => self::assertEquals($formFilesUploaded->get('image3'), $uploadedFile->getFile()),
                    default => throw new \LogicException('There is only 3 files uploaded'),
                };

                return true;
            }),
                $pathToSaveUploadedFiles,
                null
            )
            ->willReturnOnConsecutiveCalls(
                $formFilesUploadedMovedToNewPath->get('imageNoExists'),
                $formFilesUploadedMovedToNewPath->get('image2'),
                $formFilesUploadedMovedToNewPath->get('image3'),
            );

        $return = $this->object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($this->object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }
}
