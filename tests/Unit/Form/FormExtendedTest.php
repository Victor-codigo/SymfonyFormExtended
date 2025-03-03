<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Tests\Unit\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Form\FormExtended;
use VictorCodigo\SymfonyFormExtended\Form\FormMessage;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormDataClassForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Form\Fixture\FormTypeForTesting;
use VictorCodigo\SymfonyFormExtended\Tests\Unit\Trait\TestingFormTrait;
use VictorCodigo\UploadFile\Adapter\UploadFileService;
use VictorCodigo\UploadFile\Adapter\UploadedFileSymfonyAdapter;
use VictorCodigo\UploadFile\Domain\UploadedFileInterface;

class FormExtendedTest extends TestCase
{
    use TestingFormTrait;

    /**
     * @var FormInterface<mixed>|MockObject
     */
    private FormInterface&MockObject $form;
    /**
     * @var FormConfigInterface<object>&MockObject
     */
    private FormConfigInterface&MockObject $formConfig;
    private TranslatorInterface&MockObject $translator;
    private FlashBagInterface&MockObject $flashBag;
    private CsrfTokenManagerInterface&MockObject $csrfToneManager;
    private ResolvedFormTypeInterface&MockObject $resolvedFormType;
    private UploadFileService&MockObject $uploadFile;
    private Request&MockObject $request;
    private FormTypeForTesting $formType;
    private string $locale = 'locale';

    protected function setUp(): void
    {
        parent::setUp();

        $this->formConfig = $this->createMock(FormConfigInterface::class);
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

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->uploadFile = $this->createMock(UploadFileService::class);
        $this->request = $this->createMock(Request::class);
        $this->resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $this->csrfToneManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->formType = new FormTypeForTesting($this->translator, $this->csrfToneManager);
    }

    private function createFormExtended(): FormExtended
    {
        return new FormExtended(
            $this->form,
            $this->translator,
            $this->flashBag,
            $this->uploadFile,
            $this->locale
        );
    }

    #[Test]
    public function itShouldFailCreatingFormExtendedFormTypeNotImplementsInterfaceFormTypeExtendedInterface(): void
    {
        $formTypeNotImplementsFormTypeExtendedInterface = $this->createMock(FormType::class);

        $this->formConfig
            ->expects($this->any())
            ->method('getType')
            ->willReturn($this->resolvedFormType);

        $this->resolvedFormType
            ->expects($this->any())
            ->method('getInnerType')
            ->willReturn($formTypeNotImplementsFormTypeExtendedInterface);

        $this->expectException(\LogicException::class);
        $this->createFormExtended();
    }

    #[Test]
    public function itShouldGetErrorsTranslatedDeepAndFlattenAreTrue(): void
    {
        $deep = true;
        $flatten = true;
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageErrors): FormExtended => $object->addError(new FormError(
            $messageErrors->message,
            $messageErrors->template,
            $messageErrors->parameters,
            $messageErrors->pluralization,
            $object
        )));
        $messageErrorsTranslated = $this->getMessageErrorsTranslated(
            new ArrayCollection(iterator_to_array($object->getMessageErrorsTranslated(true))),
            $this->form
        );
        $this->createSubFormMethodTrans($messageErrors, $messageErrorsTranslated);

        $return = $object->getMessageErrorsTranslated($deep, $flatten);

        self::assertCount($messageErrorsTranslated->count(), $return);
        $errorTranslated = $messageErrorsTranslated->first();
        foreach ($return as $errorReturned) {
            self::assertEquals($errorTranslated, $errorReturned);

            $errorTranslated = $messageErrorsTranslated->next();
        }
    }

    #[Test]
    public function itShouldGetErrorsTranslatedDeepAndFlattenIAreFalse(): void
    {
        $deep = false;
        $flatten = false;
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageError): FormExtended => $object->addError(new FormError(
            $messageError->message,
            $messageError->template,
            $messageError->parameters,
            $messageError->pluralization
        )));
        $messageErrorsTranslated = $this->getMessageErrorsTranslated(
            new ArrayCollection(iterator_to_array($object->getMessageErrorsTranslated(true))),
            $this->form
        );

        $translationDomain = $this->exactly($messageErrors->count());
        $this->translator
            ->expects($translationDomain)
            ->method('trans')
            ->with(
                self::callback(function (string $message) use ($translationDomain, $messageErrors): bool {
                    self::assertEquals($messageErrors->get($translationDomain->numberOfInvocations() - 1)?->message, $message);

                    return true;
                }),
                self::callback(function (array $params) use ($translationDomain, $messageErrors): bool {
                    self::assertEquals($messageErrors->get($translationDomain->numberOfInvocations() - 1)?->parameters, $params);

                    return true;
                }),
                self::equalTo(FormTypeForTesting::TRANSLATION_DOMAIN),
                self::equalTo($this->locale)
            )
            ->willReturnOnConsecutiveCalls(
                $messageErrorsTranslated->get(0)?->message,
                $messageErrorsTranslated->get(1)?->message,
                $messageErrorsTranslated->get(2)?->message,
                $messageErrorsTranslated->get(3)?->message,
            );

        $return = $object->getMessageErrorsTranslated($deep, $flatten);

        self::assertCount($messageErrorsTranslated->count(), $return);
        $errorTranslated = $messageErrorsTranslated->first();
        foreach ($return as $errorReturned) {
            self::assertEquals($errorTranslated, $errorReturned);

            $errorTranslated = $messageErrorsTranslated->next();
        }
    }

    #[Test]
    public function itShouldNoErrorsFormHasNoErrors(): void
    {
        $deep = true;
        $flatten = true;

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);

        $this->translator
            ->expects($this->never())
            ->method('trans');

        $object = $this->createFormExtended();
        $return = $object->getMessageErrorsTranslated($deep, $flatten);

        self::assertCount(0, $return);
    }

    #[Test]
    public function itShouldGetMessagesOkTranslated(): void
    {
        $messagesOk = FormTypeForTesting::getFormMessageErrors();
        $messageErrorsTranslated = $this->getMessageErrorsTranslated($messagesOk, $this->form);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $this->createSubFormMethodTrans($messagesOk, $messageErrorsTranslated);

        $object = $this->createFormExtended();
        $return = $object->getMessagesSuccessTranslated();

        self::assertEquals($messageErrorsTranslated, $return);
    }

    #[Test]
    public function itShouldAddSuccessFormFlashMessages(): void
    {
        $flashBagSuccessType = 'success';
        $flashBagErrorType = 'error';
        $formSuccessMessages = $this->formType->getFormSuccessMessages();
        $formSuccessMessagesTranslated = $this->getMessageErrorsTranslated($formSuccessMessages, $this->form);

        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $this->createSubFormMethodTrans($formSuccessMessages, $formSuccessMessagesTranslated);

        $flashBagAddInvokeCount = $this->exactly($formSuccessMessages->count());
        $this->flashBag
            ->expects($flashBagAddInvokeCount)
            ->method('add')
            ->with(
                $flashBagSuccessType,
                self::callback(function (FormMessage $message) use ($formSuccessMessagesTranslated, $flashBagAddInvokeCount): bool {
                    self::assertEquals($formSuccessMessagesTranslated->get($flashBagAddInvokeCount->numberOfInvocations() - 1)?->message, $message->message);

                    return true;
                }));

        $object = $this->createFormExtended();
        $object->addFlashMessagesTranslated($flashBagSuccessType, $flashBagErrorType, true);
    }

    #[Test]
    public function itShouldAddErrorFormFlashMessages(): void
    {
        $flashBagSuccessType = 'success';
        $flashBagErrorType = 'error';
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $messageErrors = $this->createMessageErrors();
        $messageErrors->map(fn (FormMessage $messageError): FormExtended => $object->addError(new FormError(
            $messageError->message,
            $messageError->template,
            $messageError->parameters,
            $messageError->pluralization
        )));
        $errorsTranslated = $this->getMessageErrorsTranslated($messageErrors, $this->form);

        $flashBagAddInvokeCount = $this->exactly($messageErrors->count());
        $this->flashBag
            ->expects($flashBagAddInvokeCount)
            ->method('add')
            ->with(
                $flashBagErrorType,
                self::callback(function (FormMessage $message) use ($errorsTranslated, $flashBagAddInvokeCount): bool {
                    self::assertEquals($errorsTranslated->get($flashBagAddInvokeCount->numberOfInvocations() - 1)?->message, $message->message);

                    return true;
                }));

        $this->createSubFormMethodTrans($messageErrors, $errorsTranslated);

        $object->addFlashMessagesTranslated($flashBagSuccessType, $flashBagErrorType, true);
    }

    #[Test]
    public function itShouldGetFlashMessagesData(): void
    {
        $messagesType = 'messages.type';
        $messagesData = new ArrayCollection([
            new FormMessage('message 1 ', 'template 1', ['param 1' => 'value 1'], null),
            new FormMessage('message 2 ', 'template 2', ['param 2' => 'value 2'], null),
        ]);
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        $this->flashBag
            ->expects($this->once())
            ->method('get')
            ->with($messagesType)
            ->willReturn($messagesData->toArray());

        $return = $object->getFlashMessagesData($messagesType);

        self::assertEquals($messagesData, $return);
    }

    #[Test]
    public function itShouldGetFlashMessages(): void
    {
        $messagesType = 'messages.type';
        $messagesData = new ArrayCollection([
            new FormMessage('message 1 ', 'template 1', ['param 1' => 'value 1'], null),
            new FormMessage('message 2 ', 'template 2', ['param 2' => 'value 2'], null),
        ]);
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        $this->flashBag
            ->expects($this->once())
            ->method('get')
            ->with($messagesType)
            ->willReturn($messagesData->toArray());

        $return = $object->getFlashMessages($messagesType);

        self::assertEquals(
            $messagesData->map(fn (FormMessage $message): string => $message->message),
            $return,
        );
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

    /**
     * @param Collection<string, UploadedFile|null> $formFilesUploaded
     */
    private function setRequestFilesUploaded(string $formName, Collection $formFilesUploaded): void
    {
        $this->request->files = new FileBag();
        $this->request->files->set($formName, $formFilesUploaded->toArray());
    }

    #[Test]
    public function itShouldUploadFiles(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $formFilesUploadedSymfonyAdapter = $this->getFormUploadedFilesMock();
        $formFilesUploadedMovedToNewPath = $this->getFormUploadedFilesMock();
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

        $return = $object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldNotUploadFilesRequestHasNot(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
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

        $return = $object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldNotUploadFilesRequestImageIsNull(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
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

        $return = $object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($object, $return);
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
        $object = $this->createFormExtended();
        $formFilesUploadedSymfonyAdapter = $this->getFormUploadedFilesMock();
        $formFilesUploadedMovedToNewPath = $this->getFormUploadedFilesMock();
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

        $return = $object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($object, $return);
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
        $object = $this->createFormExtended();
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

        $return = $object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldUploadFilesWithFilesFileNameUploadedDoesNotExistsInTheDataClass(): void
    {
        $formName = 'formName';
        $pathToSaveUploadedFiles = 'path/to/save/files/uploaded';
        $filenamesToBeReplacedByUploaded = [];
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $formFilesUploadedSymfonyAdapter = $this->getFormUploadedFilesMock();
        $image1 = $formFilesUploadedSymfonyAdapter->get('image1') ?: throw new \LogicException('Image file 1 does not exist');
        $formFilesUploadedSymfonyAdapter->remove('image1');
        $formFilesUploadedSymfonyAdapter->set('imageNoExists', $image1);
        $formFilesUploadedMovedToNewPath = $this->getFormUploadedFilesMock();
        $formFilesUploadedMovedToNewPath->remove('image1');
        $formFilesUploadedMovedToNewPath->set('imageNoExists', $image1);
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

        $return = $object->uploadFiles($this->request, $pathToSaveUploadedFiles, $filenamesToBeReplacedByUploaded);

        self::assertEquals($object, $return);
        self::assertEquals($formDataClassExpected, $formDataClass);
    }

    #[Test]
    public function itShouldGetTheIterator(): void
    {
        $iterator = new \ArrayIterator();
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        // @phpstan-ignore phpunit.mockMethod
        $this->form
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn($iterator);

        $return = $object->getIterator();

        self::assertEquals($iterator, $return);
    }

    #[Test]
    public function itShouldFailGettingTheIteratorNotImplementsIteratorAggregate(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $objectReflection = new \ReflectionClass($object);
        $objectReflection
            ->getProperty('form')
            ->setValue($object, $this->createMock(FormInterface::class));

        $this->expectException(LogicException::class);
        $object->getIterator();
    }

    #[Test]
    public function itShouldClearErrors(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();

        // @phpstan-ignore phpunit.mockMethod
        $this->form
            ->expects($this->once())
            ->method('clearErrors')
            ->with(true);

        $return = $object->clearErrors(true);

        self::assertEquals($object, $return);
    }

    #[Test]
    public function itShouldClearingErrorsNotImplementsClearableErrorsInterface(): void
    {
        $this->createStubForGetInnerType($this->form, $this->formConfig, $this->resolvedFormType, $this->formType);
        $object = $this->createFormExtended();
        $objectReflection = new \ReflectionClass($object);
        $objectReflection
            ->getProperty('form')
            ->setValue($object, $this->createMock(FormInterface::class));

        $this->expectException(LogicException::class);
        $object->clearErrors(true);
    }
}
