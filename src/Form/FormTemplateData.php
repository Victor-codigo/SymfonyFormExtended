<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

readonly class FormTemplateData
{
    /**
     * @param array<int, string> $messagesError
     * @param array<int, string> $messagesOk
     */
    public function __construct(
        public object $fieldNames,
        public object $constraintValues,
        public string $csrfToken,
        public bool $isValidForm,
        public array $messagesError,
        public array $messagesOk,
    ) {
    }
}
