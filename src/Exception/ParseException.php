<?php

declare(strict_types=1);

namespace RunOpenCode\Component\XmlParser\Exception;

class ParseException extends RuntimeException
{
    public function __construct(
        string              $message,
        public readonly int $errorCode,
        public readonly int $errorLineno
    ) {
        parent::__construct($message);
    }
}
