<?php

// Copyright 2026 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Describes a feature supported by a DeepL resource, as returned by
 * {@see DeepLClient::getLanguageResources()}.
 */
class ResourceFeature
{
    /** @var string Feature name, for example 'glossary' or 'formality'. */
    public $name;

    /** @var bool|null True if the feature requires the source language to support it. */
    public $needsSourceSupport;

    /** @var bool|null True if the feature requires the target language to support it. */
    public $needsTargetSupport;

    public function __construct(
        string $name,
        ?bool $needsSourceSupport = null,
        ?bool $needsTargetSupport = null
    ) {
        $this->name = $name;
        $this->needsSourceSupport = $needsSourceSupport;
        $this->needsTargetSupport = $needsTargetSupport;
    }

    public static function fromJson(array $json): ResourceFeature
    {
        return new ResourceFeature(
            $json['name'],
            $json['needs_source_support'] ?? null,
            $json['needs_target_support'] ?? null
        );
    }
}
