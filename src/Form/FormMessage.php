<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form;

readonly class FormMessage
{
    /**
     * @param array<array-key, mixed> $parameters
     */
    public function __construct(
        public string $message,
        public string $template,
        public array $parameters,
        public ?int $pluralization,
    ) {
    }
}
