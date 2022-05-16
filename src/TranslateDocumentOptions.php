<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Options that can be specified when translating documents.
 * @see Translator::translateDocument()
 * @see Translator::uploadDocument()
 */
final class TranslateDocumentOptions
{
    /** Controls whether translations should lean toward formal or informal language. */
    public const FORMALITY = 'formality';

    /** Set to string containing a glossary ID to use the glossary for translation. */
    public const GLOSSARY = 'glossary';
}
