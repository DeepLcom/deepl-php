<?php

namespace DeepL;

use JsonException;

class InvalidContentException extends DeepLException
{
    public function __construct(JsonException $exception)
    {
        parent::__construct($exception->getMessage(), $exception->getCode(), $exception);
    }
}
