<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use VictorCodigo\SymfonyFormExtended\Type\FormTypeBase;
use VictorCodigo\SymfonyFormExtended\Type\FormTypeExtendedInterface;

class FormExtendedMessages
{
    /**
     * @param FormInterface<mixed> $form
     */
    public function __construct(
        private FormInterface $form,
        private TranslatorInterface $translator,
        private FlashBagInterface $flashBag,
        private string $translationDomain,
        private ?string $locale,
    ) {
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
}
