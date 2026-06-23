<?php

// Copyright 2026 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

/**
 * Pins the wire shape of the request bodies produced after the JSON migration.
 * Unlike RequestShapeTest (method/path/headers) and the validating mock
 * (spec-validity, which often allows several types), these tests assert the
 * exact JSON types the SDK sends, so a regression to the old form-encoded
 * strings would fail here.
 */
class RequestBodyShapeTest extends DeepLTestBase
{
    private const DUMMY_SERVER_URL = 'http://localhost';
    private const DUMMY_AUTH_KEY = 'test-auth-key';

    private function makeClient(string $responseBody): CapturingHttpClient
    {
        return new CapturingHttpClient($responseBody);
    }

    private function makeOptions(CapturingHttpClient $client): array
    {
        return [
            TranslatorOptions::SERVER_URL => self::DUMMY_SERVER_URL,
            TranslatorOptions::HTTP_CLIENT => $client,
        ];
    }

    private function makeCapturingTranslator(CapturingHttpClient $client): Translator
    {
        return new Translator(self::DUMMY_AUTH_KEY, $this->makeOptions($client));
    }

    private function makeCapturingDeepLClient(CapturingHttpClient $client): DeepLClient
    {
        return new DeepLClient(self::DUMMY_AUTH_KEY, $this->makeOptions($client));
    }

    private function assertJsonContentType(CapturingHttpClient $client): void
    {
        $this->assertStringContainsString(
            'application/json',
            $client->getLastRequest()->getHeaderLine('Content-Type')
        );
    }

    private static function translateResponse(): string
    {
        return json_encode([
            'translations' => [
                [
                    'text' => 'Hallo',
                    'detected_source_language' => 'EN',
                    'billed_characters' => 5,
                ],
                [
                    'text' => 'Welt',
                    'detected_source_language' => 'EN',
                    'billed_characters' => 4,
                ],
            ],
        ]);
    }

    public function testSingleTextIsSentAsJsonArray(): void
    {
        $client = $this->makeClient(self::translateResponse());
        $this->makeCapturingTranslator($client)->translateText('Hello', null, 'de');

        $this->assertJsonContentType($client);
        $body = $client->decodeBody();
        $this->assertSame(['Hello'], $body['text']);
        // show_billed_characters must be a JSON boolean, not the old "1" string
        $this->assertSame(true, $body['show_billed_characters']);
    }

    public function testMultipleTextsArePreservedAsArray(): void
    {
        $client = $this->makeClient(self::translateResponse());
        $this->makeCapturingTranslator($client)->translateText(['Hello', 'World'], null, 'de');

        $body = $client->decodeBody();
        $this->assertSame(['Hello', 'World'], $body['text']);
    }

    public function testBooleanOptionsAreSentAsBooleans(): void
    {
        $client = $this->makeClient(self::translateResponse());
        $this->makeCapturingTranslator($client)->translateText('Hello', null, 'de', [
            TranslateTextOptions::PRESERVE_FORMATTING => true,
            TranslateTextOptions::OUTLINE_DETECTION => false,
        ]);

        $body = $client->decodeBody();
        $this->assertSame(true, $body['preserve_formatting']);
        $this->assertSame(false, $body['outline_detection']);
    }

    public function testSplitSentencesStaysAnEnumString(): void
    {
        $client = $this->makeClient(self::translateResponse());
        $this->makeCapturingTranslator($client)->translateText('Hello', null, 'de', [
            TranslateTextOptions::SPLIT_SENTENCES => 'on',
        ]);

        // split_sentences is a string enum ('0'/'1'/'nonewlines'), not a boolean
        $this->assertSame('1', $client->decodeBody()['split_sentences']);
    }

    public function testTagListFromStringIsSplitIntoArray(): void
    {
        $client = $this->makeClient(self::translateResponse());
        $this->makeCapturingTranslator($client)->translateText('Hello', null, 'de', [
            TranslateTextOptions::IGNORE_TAGS => 'a,b,c',
        ]);

        $this->assertSame(['a', 'b', 'c'], $client->decodeBody()['ignore_tags']);
    }

    public function testTagListFromArrayIsSentAsArray(): void
    {
        $client = $this->makeClient(self::translateResponse());
        $this->makeCapturingTranslator($client)->translateText('Hello', null, 'de', [
            TranslateTextOptions::SPLITTING_TAGS => ['x', 'y'],
        ]);

        $this->assertSame(['x', 'y'], $client->decodeBody()['splitting_tags']);
    }

    public function testTranslationMemoryThresholdIsSentAsInteger(): void
    {
        $client = $this->makeClient(self::translateResponse());
        $this->makeCapturingTranslator($client)->translateText('Hello', null, 'de', [
            TranslateTextOptions::TRANSLATION_MEMORY_ID => 'tm-1234',
            TranslateTextOptions::TRANSLATION_MEMORY_THRESHOLD => 50,
        ]);

        $this->assertSame(50, $client->decodeBody()['translation_memory_threshold']);
    }

    public function testGetLanguagesSendsTypeAsQueryParamWithEmptyBody(): void
    {
        $client = $this->makeClient(json_encode([['language' => 'EN', 'name' => 'English']]));
        $this->makeCapturingTranslator($client)->getSourceLanguages();

        $this->assertStringContainsString('type=source', $client->getLastRequestQuery());
        $this->assertSame('', $client->getLastRequestBody());
    }

    public function testCreateGlossarySendsJsonWithStringEntries(): void
    {
        $client = $this->makeClient(json_encode([
            'glossary_id' => 'abc',
            'name' => 'name',
            'ready' => true,
            'source_lang' => 'en',
            'target_lang' => 'de',
            'creation_time' => '2024-01-01T00:00:00Z',
            'entry_count' => 1,
        ]));
        $this->makeCapturingTranslator($client)->createGlossary(
            'name',
            'en',
            'de',
            GlossaryEntries::fromEntries(['Hello' => 'Hallo'])
        );

        $this->assertJsonContentType($client);
        $body = $client->decodeBody();
        $this->assertSame('tsv', $body['entries_format']);
        $this->assertIsString($body['entries']);
        $this->assertStringContainsString('Hallo', $body['entries']);
    }

    public function testRephraseSendsTextAsJsonArray(): void
    {
        $client = $this->makeClient(json_encode([
            'improvements' => [
                [
                    'text' => 'Hi',
                    'detected_source_language' => 'EN',
                    'target_language' => 'EN',
                ],
            ],
        ]));
        $this->makeCapturingDeepLClient($client)->rephraseText('Hello', 'en-US');

        $this->assertJsonContentType($client);
        $this->assertSame(['Hello'], $client->decodeBody()['text']);
    }
}
