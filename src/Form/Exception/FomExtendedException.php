<?php

declare(strict_types=1);

namespace VictorCodigo\SymfonyFormExtended\Form\Exception;

class FomExtendedException extends \Exception
{
    final private function __construct(string $message, int $code, ?\Throwable $throwable)
    {
        parent::__construct($message, $code, $throwable);
    }

    public static function fromMessage(string $message): static
    {
        return new static ($message,0, null);
    }
}
