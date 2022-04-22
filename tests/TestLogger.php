<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

class TestLogger implements LoggerInterface
{
    public $content = "";

    public function info(string $message, array $context = array())
    {
        $this->content .= $message . PHP_EOL;
    }

    public function debug(string $message, array $context = array())
    {
        $this->content .= $message . PHP_EOL;
    }
}
