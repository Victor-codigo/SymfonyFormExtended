<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\ClearableErrorsInterface;
use Symfony\Component\Form\Exception\AlreadySubmittedException;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\OutOfBoundsException;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\Exception\FormSizeFileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\PropertyAccess\PropertyPathInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Type\FormTypeBase;
use VictorCodigo\SymfonyFormExtended\Type\FormTypeExtendedInterface;
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

/**
 * @implements \IteratorAggregate<string, mixed>
 */
class FormExtended implements FormExtendedInterface, \IteratorAggregate, ClearableErrorsInterface
{
    /**
     * @var FormInterface<mixed>
     */
    private FormInterface $form;
    private TranslatorInterface $translator;
    private FlashBagInterface $flashBag;
    private UploadFileService $uploadFile;
    public readonly string $translationDomain;
    public readonly ?string $locale;

    /**
     * @param FormInterface<mixed> $form
     */
    public function __construct(FormInterface $form, TranslatorInterface $translator, FlashBagInterface $flashBag, UploadFileService $uploadFile, ?string $locale)
    {
        $this->form = $form;
        $this->translator = $translator;
        $this->translationDomain = $this->getTranslationDomain();
        $this->flashBag = $flashBag;
        $this->uploadFile = $uploadFile;
        $this->locale = $locale;
    }

    /**
     * @return Collection<int, FormMessage>
     */
    public function getMessageErrorsTranslated(bool $deep = false, bool $flatten = true): Collection
    {
        $errors = $this->form->getErrors($deep, $flatten);

        $errorsTranslated = [];
        foreach ($errors as $error) {
            $errorTranslated = new FormMessage(
                $this->translator->trans($error->getMessage(), $error->getMessageParameters(), $this->translationDomain, $this->locale),
                $error->getMessageTemplate(),
                $error->getMessageParameters(),
                $error->getMessagePluralization(),
            );

            $errorsTranslated[] = $errorTranslated;
        }

        return new ArrayCollection($errorsTranslated);
    }

    /**
     * Returns the errors of this form.
     *
     * @param bool $deep    Whether to include errors of child forms as well
     * @param bool $flatten Whether to flatten the list of errors in case
     *                      $deep is set to true
     *
     * @return FormErrorIterator<FormError>
     */
    public function getErrors(bool $deep = false, bool $flatten = true): FormErrorIterator
    {
        return $this->form->getErrors($deep, $flatten);
    }

    /**
     * @return Collection<int, FormMessage>
     */
    public function getMessagesSuccessTranslated(): Collection
    {
        /** @var FormTypeExtendedInterface<FormTypeBase<object>> */
        $formType = $this->form->getConfig()->getType()->getInnerType();

        return $formType
            ->getFormSuccessMessages()
            ->map(fn (FormMessage $messageOk): FormMessage => new FormMessage(
                $this->translator->trans($messageOk->message, $messageOk->parameters, $this->translationDomain, $this->locale),
                $messageOk->template,
                $messageOk->parameters,
                $messageOk->pluralization,
            ));
    }

    public function addFlashMessagesTranslated(string $messagesSuccessType, string $messagesErrorType, bool $deep): void
    {
        $errors = $this->getMessageErrorsTranslated($deep);

        if (0 === $errors->count()) {
            $this->getMessagesSuccessTranslated()
                 ->map(fn (FormMessage $message) => $this->flashBag->add($messagesSuccessType, $message));

            return;
        }

        foreach ($errors as $error) {
            $this->flashBag->add($messagesErrorType, $error);
        }
    }

    /**
     * @return Collection<int, FormMessage>
     */
    public function getFlashMessagesData(string $messagesType): Collection
    {
        /** @var array<int, FormMessage> */
        $messagesData = $this->flashBag->get($messagesType);

        return new ArrayCollection($messagesData);
    }

    /**
     * @return Collection<int, string>
     */
    public function getFlashMessages(string $messagesType): Collection
    {
        $messages = $this->getFlashMessagesData($messagesType);

        return $messages->map(fn (FormMessage $message): string => $message->message);
    }

    /**
     * @throws \LogicException
     */
    private function getTranslationDomain(): string
    {
        $formType = $this->form->getConfig()->getType()->getInnerType();

        if (!$formType instanceof FormTypeExtendedInterface) {
            throw new \LogicException('Form type must implement FormTypeTranslatedInterface');
        }

        return $formType::TRANSLATION_DOMAIN;
    }

    /**
     * Inspects the given request and calls {@link submit()} if the form was
     * submitted.
     *
     * Internally, the request is forwarded to the configured
     * {@link RequestHandlerInterface} instance, which determines whether to
     * submit the form or not.
     *
     * @param Request|null $request
     *
     * @return $this
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
    public function handleRequest(mixed $request = null): static
    {
        $this->form->handleRequest($request);

        return $this;
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

    /**
     * Sets the parent form.
     *
     * @param FormInterface<mixed>|null $parent The parent form or null if it's the root
     *
     * @return $this
     *
     * @throws AlreadySubmittedException if the form has already been submitted
     * @throws LogicException            when trying to set a parent for a form with
     *                                   an empty name
     */
    public function setParent(?FormInterface $parent): static
    {
        $this->form->setParent($parent);

        return $this;
    }

    /**
     * Returns the parent form.
     *
     * @return FormInterface<Form>
     */
    public function getParent(): ?FormInterface
    {
        return $this->form->getParent();
    }

    /**
     * Adds or replaces a child to the form.
     *
     * @param FormInterface<mixed>|string $child   The FormInterface instance or the name of the child
     * @param string|null                 $type    The child's type, if a name was passed
     * @param array<array-key, mixed>     $options The child's options, if a name was passed
     *
     * @return $this
     *
     * @throws AlreadySubmittedException if the form has already been submitted
     * @throws LogicException            when trying to add a child to a non-compound form
     * @throws UnexpectedTypeException   if $child or $type has an unexpected type
     */
    public function add(FormInterface|string $child, ?string $type = null, array $options = []): static
    {
        $this->form->add($child, $type, $options);

        return $this;
    }

    /**
     * Returns the child with the given name.
     *
     * @return FormInterface<mixed>
     *
     * @throws OutOfBoundsException if the named child does not exist
     */
    public function get(string $name): FormInterface
    {
        return $this->form->get($name);
    }

    /**
     * Returns whether a child with the given name exists.
     */
    public function has(string $name): bool
    {
        return $this->form->has($name);
    }

    /**
     * Removes a child from the form.
     *
     * @return $this
     *
     * @throws AlreadySubmittedException if the form has already been submitted
     */
    public function remove(string $name): static
    {
        $this->form->remove($name);

        return $this;
    }

    /**
     * Returns all children in this group.
     *
     * @return array<FormInterface<mixed>>
     */
    public function all(): array
    {
        return $this->form->all();
    }

    /**
     * Updates the form with default model data.
     *
     * @param mixed $modelData The data formatted as expected for the underlying object
     *
     * @return $this
     *
     * @throws AlreadySubmittedException     If the form has already been submitted
     * @throws LogicException                if the view data does not match the expected type
     *                                       according to {@link FormConfigInterface::getDataClass}
     * @throws RuntimeException              If listeners try to call setData in a cycle or if
     *                                       the form inherits data from its parent
     * @throws TransformationFailedException if the synchronization failed
     */
    public function setData(mixed $modelData): static
    {
        $this->form->setData($modelData);

        return $this;
    }

    /**
     * Returns the model data in the format needed for the underlying object.
     *
     * @return mixed When the field is not submitted, the default data is returned.
     *               When the field is submitted, the default data has been bound
     *               to the submitted view data.
     *
     * @throws RuntimeException If the form inherits data but has no parent
     */
    public function getData(): mixed
    {
        return $this->form->getData();
    }

    /**
     * Returns the normalized data of the field, used as internal bridge
     * between model data and view data.
     *
     * @return mixed When the field is not submitted, the default data is returned.
     *               When the field is submitted, the normalized submitted data
     *               is returned if the field is synchronized with the view data,
     *               null otherwise.
     *
     * @throws RuntimeException If the form inherits data but has no parent
     */
    public function getNormData(): mixed
    {
        return $this->form->getNormData();
    }

    /**
     * Returns the view data of the field.
     *
     * It may be defined by {@link FormConfigInterface::getDataClass}.
     *
     * There are two cases:
     *
     * - When the form is compound the view data is mapped to the children.
     *   Each child will use its mapped data as model data.
     *   It can be an array, an object or null.
     *
     * - When the form is simple its view data is used to be bound
     *   to the submitted data.
     *   It can be a string or an array.
     *
     * In both cases the view data is the actual altered data on submission.
     *
     * @throws RuntimeException If the form inherits data but has no parent
     */
    public function getViewData(): mixed
    {
        return $this->form->getViewData();
    }

    /**
     * Returns the extra submitted data.
     *
     * @return array<array-key, mixed> The submitted data which do not belong to a child
     */
    public function getExtraData(): array
    {
        return $this->form->getExtraData();
    }

    /**
     * Returns the form's configuration.
     *
     * @return FormConfigInterface<object>
     */
    public function getConfig(): FormConfigInterface
    {
        return $this->form->getConfig();
    }

    /**
     * Returns whether the form is submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->form->isSubmitted();
    }

    /**
     * Returns the name by which the form is identified in forms.
     *
     * Only root forms are allowed to have an empty name.
     */
    public function getName(): string
    {
        return $this->form->getName();
    }

    /**
     * Returns the property path that the form is mapped to.
     */
    public function getPropertyPath(): ?PropertyPathInterface
    {
        return $this->form->getPropertyPath();
    }

    /**
     * Adds an error to this form.
     *
     * @return $this
     */
    public function addError(FormError $error): static
    {
        $this->form->addError($error);

        return $this;
    }

    /**
     * Returns whether the form and all children are valid.
     *
     * @throws LogicException if the form is not submitted
     */
    public function isValid(): bool
    {
        return $this->form->isValid();
    }

    /**
     * Returns whether the form is required to be filled out.
     *
     * If the form has a parent and the parent is not required, this method
     * will always return false. Otherwise the value set with setRequired()
     * is returned.
     */
    public function isRequired(): bool
    {
        return $this->form->isRequired();
    }

    /**
     * Returns whether this form is disabled.
     *
     * The content of a disabled form is displayed, but not allowed to be
     * modified. The validation of modified disabled forms should fail.
     *
     * Forms whose parents are disabled are considered disabled regardless of
     * their own state.
     */
    public function isDisabled(): bool
    {
        return $this->form->isDisabled();
    }

    /**
     * Returns whether the form is empty.
     */
    public function isEmpty(): bool
    {
        return $this->form->isEmpty();
    }

    /**
     * Returns whether the data in the different formats is synchronized.
     *
     * If the data is not synchronized, you can get the transformation failure
     * by calling {@link getTransformationFailure()}.
     *
     * If the form is not submitted, this method always returns true.
     */
    public function isSynchronized(): bool
    {
        return $this->form->isSynchronized();
    }

    /**
     * Returns the data transformation failure, if any, during submission.
     */
    public function getTransformationFailure(): ?TransformationFailedException
    {
        return $this->form->getTransformationFailure();
    }

    /**
     * Initializes the form tree.
     *
     * Should be called on the root form after constructing the tree.
     *
     * @return $this
     *
     * @throws RuntimeException If the form is not the root
     */
    public function initialize(): static
    {
        $this->form->initialize();

        return $this;
    }

    /**
     * Submits data to the form.
     *
     * @param string|array<array-key, mixed>|null $submittedData The submitted data
     * @param bool                                $clearMissing  Whether to set fields to NULL
     *                                                           when they are missing in the
     *                                                           submitted data. This argument
     *                                                           is only used in compound form
     *
     * @return $this
     *
     * @throws AlreadySubmittedException if the form has already been submitted
     */
    public function submit(string|array|null $submittedData, bool $clearMissing = true): static
    {
        $this->form->submit($submittedData, $clearMissing);

        return $this;
    }

    /**
     * Returns the root of the form tree.
     *
     * @return FormInterface<mixed>
     */
    public function getRoot(): FormInterface
    {
        return $this->form->getRoot();
    }

    /**
     * Returns whether the field is the root of the form tree.
     */
    public function isRoot(): bool
    {
        return $this->form->isRoot();
    }

    public function createView(?FormView $parent = null): FormView
    {
        return $this->form->createView($parent);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->form->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->form->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->form->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->offsetUnset($offset);
    }

    public function count(): int
    {
        return $this->form->count();
    }

    /**
     * @return \Traversable<array-key, mixed>
     *
     * @throws LogicException
     */
    public function getIterator(): \Traversable
    {
        if (!$this->form instanceof \IteratorAggregate) {
            throw new LogicException('The form must implement IteratorAggregate to use getIterator');
        }

        return $this->form->getIterator();
    }

    /**
     * @throws LogicException
     */
    public function clearErrors(bool $deep = false): static
    {
        if (!$this->form instanceof ClearableErrorsInterface) {
            throw new LogicException('The form must implement ClearableErrorsInterface to use clearErrors');
        }

        $this->form->clearErrors($deep);

        return $this;
    }
}
