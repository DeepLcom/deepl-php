<?php

// Copyright 2025 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use JsonException;

/**
 * Information about a translation memory.
 */
class TranslationMemoryInfo
{
    /** @var string Unique ID assigned to the translation memory. */
    public $translationMemoryId;

    /** @var string User-defined name assigned to the translation memory. */
    public $name;

    /** @var string Language code for the source language of the translation memory. */
    public $sourceLanguage;

    /** @var string[] List of target language codes for the translation memory. */
    public $targetLanguages;

    /** @var int Number of segments in the translation memory. */
    public $segmentCount;

    public function __construct(
        string $translationMemoryId,
        string $name,
        string $sourceLanguage,
        array $targetLanguages,
        int $segmentCount
    ) {
        $this->translationMemoryId = $translationMemoryId;
        $this->name = $name;
        $this->sourceLanguage = $sourceLanguage;
        $this->targetLanguages = $targetLanguages;
        $this->segmentCount = $segmentCount;
    }

    /**
     * @param string|TranslationMemoryInfo $translationMemory Translation memory ID or TranslationMemoryInfo.
     */
    public static function getTranslationMemoryId($translationMemory): string
    {
        return is_string($translationMemory) ? $translationMemory : $translationMemory->translationMemoryId;
    }

    /**
     * @throws InvalidContentException
     */
    public static function fromJson(array $json): TranslationMemoryInfo
    {
        return new TranslationMemoryInfo(
            $json['translation_memory_id'],
            $json['name'],
            $json['source_language'],
            $json['target_languages'] ?? [],
            $json['segment_count'] ?? 0
        );
    }

    /**
     * @throws InvalidContentException
     */
    public static function parseList(string $content): array
    {
        try {
            $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidContentException($exception);
        }

        $result = [];
        $translationMemories = $decoded['translation_memories'] ?? [];
        foreach ($translationMemories as $object) {
            $result[] = self::fromJson($object);
        }
        return $result;
    }

    public function __toString(): string
    {
        return "TranslationMemory \"{$this->name}\" ({$this->translationMemoryId})";
    }
}
