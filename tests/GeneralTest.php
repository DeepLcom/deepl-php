<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use Psr\Http\Client\ClientInterface;

class GeneralTest extends DeepLTestBase
{
    /**
     * @dataProvider provideHttpClient
     */
    public function testEmptyAuthKey(?ClientInterface $httpClient)
    {
        $this->expectException(DeepLException::class);
        new Translator('', [
            TranslatorOptions::SERVER_URL => $this->serverUrl,
            TranslatorOptions::HTTP_CLIENT => $httpClient
        ]);
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testInvalidAuthKey(?ClientInterface $httpClient)
    {
        $translator = new Translator('invalid', [
            TranslatorOptions::SERVER_URL => $this->serverUrl,
            TranslatorOptions::HTTP_CLIENT => $httpClient
        ]);

        $this->expectException(AuthorizationException::class);
        $translator->getUsage();
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testInvalidServerUrl(?ClientInterface $httpClient)
    {
        new Translator($this->authKey, [
            TranslatorOptions::SERVER_URL => null,
            TranslatorOptions::HTTP_CLIENT => $httpClient
        ]);

        $this->expectException(DeepLException::class);
        new Translator($this->authKey, [
            TranslatorOptions::SERVER_URL => false,
            TranslatorOptions::HTTP_CLIENT => $httpClient
        ]);
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testUsage(?ClientInterface $httpClient)
    {
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $usage = $translator->getUsage();
        $this->assertStringContainsString('Usage this billing period', strval($usage));
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testLogger(?ClientInterface $httpClient)
    {
        $logger = new TestLogger();
        $translator = $this->makeTranslator([
            TranslatorOptions::LOGGER => $logger,
            TranslatorOptions::HTTP_CLIENT => $httpClient
        ]);
        $translator->getUsage();
        $this->assertStringContainsString("Request to DeepL API", $logger->content);
        $this->assertStringContainsString("DeepL API response", $logger->content);
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testLanguage(?ClientInterface $httpClient)
    {
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $sourceLanguages = $translator->getSourceLanguages();
        foreach ($sourceLanguages as $sourceLanguage) {
            if ($sourceLanguage->code === 'en') {
                $this->assertEquals('English', $sourceLanguage->name);
            }
            $this->assertNull($sourceLanguage->supportsFormality);
        }

        $targetLanguages = $translator->getTargetLanguages();
        foreach ($targetLanguages as $targetLanguage) {
            if ($targetLanguage->code === 'de') {
                $this->assertEquals('German', $targetLanguage->name);
            }
            $this->assertNotNull($targetLanguage->supportsFormality);
        }
    }

    /**
     * @dataProvider provideHttpClient
     * @throws DeepLException
     */
    public function testGetLanguagesV3(?ClientInterface $httpClient)
    {
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);

        $resources = [
            LanguageResource::RESOURCE_TRANSLATE_TEXT,
            LanguageResource::RESOURCE_TRANSLATE_DOCUMENT,
            LanguageResource::RESOURCE_GLOSSARY,
            LanguageResource::RESOURCE_WRITE,
            LanguageResource::RESOURCE_VOICE,
            LanguageResource::RESOURCE_STYLE_RULES,
            LanguageResource::RESOURCE_TRANSLATION_MEMORY,
        ];
        foreach ($resources as $resource) {
            $languages = $client->getLanguagesForResource($resource);
            $this->assertIsArray($languages);
            $this->assertGreaterThan(0, count($languages));
            $byCode = [];
            foreach ($languages as $language) {
                $this->assertGreaterThan(0, strlen($language->code));
                $this->assertGreaterThan(0, strlen($language->name));
                $this->assertGreaterThan(0, strlen($language->status));
                $this->assertIsBool($language->usableAsSource);
                $this->assertIsBool($language->usableAsTarget);
                $this->assertIsArray($language->features);
                foreach ($language->features as $featureName => $feature) {
                    // features is a map keyed by feature name; the key must be the name, not an index
                    $this->assertIsString($featureName);
                    $this->assertNotEmpty($featureName);
                    $this->assertInstanceOf(LanguageFeature::class, $feature);
                    $this->assertGreaterThan(0, strlen($feature->status));
                }
                $byCode[$language->code] = $language;
            }
            if ($resource === LanguageResource::RESOURCE_TRANSLATE_TEXT) {
                $this->assertArrayHasKey('de', $byCode);
                $this->assertGreaterThan(0, strlen($byCode['de']->name));
            }
        }
    }

    /**
     * @dataProvider provideHttpClient
     * @throws DeepLException
     */
    public function testGetLanguagesV3RegionalVariants(?ClientInterface $httpClient)
    {
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $languages = $client->getLanguagesForResource(LanguageResource::RESOURCE_TRANSLATE_TEXT);
        $codes = [];
        foreach ($languages as $language) {
            $codes[] = $language->code;
        }
        $this->assertContains('en-US', $codes);
        $this->assertContains('pt-BR', $codes);
    }

    /**
     * @dataProvider provideHttpClient
     * @throws DeepLException
     */
    public function testGetLanguagesV3InvalidResource(?ClientInterface $httpClient)
    {
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $this->expectException(DeepLException::class);
        $this->expectExceptionMessage('Bad request');
        $client->getLanguagesForResource('not_a_resource');
    }

    public function testGetLanguagesV3EmptyResource()
    {
        $client = $this->makeDeeplClient();
        $this->expectException(DeepLException::class);
        $this->expectExceptionMessage('resource must be a non-empty string');
        $client->getLanguagesForResource('');
    }

    /**
     * @dataProvider provideHttpClient
     * @throws DeepLException
     */
    public function testGetLanguageResources(?ClientInterface $httpClient)
    {
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $resources = $client->getLanguageResources();
        $this->assertIsArray($resources);
        $this->assertGreaterThan(0, count($resources));

        $byName = [];
        foreach ($resources as $resource) {
            $this->assertGreaterThan(0, strlen($resource->resource));
            $this->assertIsArray($resource->features);
            foreach ($resource->features as $featureName => $feature) {
                // features is a map keyed by feature name; resource features carry needs_* flags, not a status
                $this->assertIsString($featureName);
                $this->assertInstanceOf(ResourceFeature::class, $feature);
                $this->assertSame($featureName, $feature->name);
                $this->assertTrue($feature->needsSourceSupport === null || is_bool($feature->needsSourceSupport));
                $this->assertTrue($feature->needsTargetSupport === null || is_bool($feature->needsTargetSupport));
            }
            $byName[$resource->resource] = $resource;
        }

        // translate_text is always present and exposes the glossary feature with both support flags
        $this->assertArrayHasKey(LanguageResource::RESOURCE_TRANSLATE_TEXT, $byName);
        $translateText = $byName[LanguageResource::RESOURCE_TRANSLATE_TEXT];
        $this->assertArrayHasKey('glossary', $translateText->features);
        $this->assertTrue($translateText->features['glossary']->needsSourceSupport);
        $this->assertTrue($translateText->features['glossary']->needsTargetSupport);
    }

    /**
     * Server-independent parsing guards for the v3 language models.
     */
    public function testV3LanguageModelParsing()
    {
        $languages = LanguageSupport::parseList(json_encode([
            [
                'lang' => 'de',
                'name' => 'German',
                'status' => 'stable',
                'usable_as_source' => true,
                'usable_as_target' => true,
                'features' => ['tag_handling' => ['status' => 'stable']],
            ],
            [
                'lang' => 'en-US',
                'name' => 'English (American)',
                'status' => 'stable',
                'usable_as_source' => false,
                'usable_as_target' => true,
                'features' => new \stdClass(),
            ],
        ]));
        $this->assertSame('de', $languages[0]->code);
        $this->assertSame('German', $languages[0]->name);
        $this->assertTrue($languages[0]->usableAsSource);
        $this->assertArrayHasKey('tag_handling', $languages[0]->features);
        $this->assertSame('stable', $languages[0]->features['tag_handling']->status);
        $this->assertTrue($languages[0]->supports(LanguageFeature::FEATURE_TAG_HANDLING));
        $this->assertFalse($languages[0]->supports(LanguageFeature::FEATURE_FORMALITY));
        // empty features object parses to an empty map, not an error
        $this->assertSame([], $languages[1]->features);
        $this->assertFalse($languages[1]->supports(LanguageFeature::FEATURE_FORMALITY));
        $this->assertFalse($languages[1]->usableAsSource);

        $resources = LanguageResource::parseList(json_encode([
            [
                'name' => 'translate_text',
                'features' => [
                    ['name' => 'glossary', 'needs_source_support' => true, 'needs_target_support' => true],
                    ['name' => 'formality', 'needs_target_support' => true],
                ],
            ],
            ['name' => 'glossary', 'features' => []],
        ]));
        $this->assertSame('translate_text', $resources[0]->resource);
        $this->assertTrue($resources[0]->supports(LanguageFeature::FEATURE_GLOSSARY));
        $this->assertFalse($resources[0]->supports(LanguageFeature::FEATURE_TONE));
        $this->assertArrayHasKey('glossary', $resources[0]->features);
        $this->assertSame('glossary', $resources[0]->features['glossary']->name);
        $this->assertTrue($resources[0]->features['glossary']->needsSourceSupport);
        // a flag absent in the payload stays null rather than defaulting to false
        $this->assertNull($resources[0]->features['formality']->needsSourceSupport);
        $this->assertTrue($resources[0]->features['formality']->needsTargetSupport);
        $this->assertSame([], $resources[1]->features);
    }

    /**
     * @dataProvider provideHttpClient
     * @throws DeepLException
     */
    public function testGlossaryLanguage(?ClientInterface $httpClient)
    {
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $glossaryLanguagePairs = $translator->getGlossaryLanguages();
        $this->assertGreaterThan(0, count($glossaryLanguagePairs));
        foreach ($glossaryLanguagePairs as $glossaryLanguagePair) {
            $this->assertGreaterThan(0, strlen($glossaryLanguagePair->sourceLang));
            $this->assertGreaterThan(0, strlen($glossaryLanguagePair->targetLang));
        }
    }

    /**
     * @dataProvider provideHttpClient
     * @throws DeepLException
     */
    public function testProxyUsage(?ClientInterface $httpClient)
    {
        $this->needsMockProxyServer();
        $this->sessionExpectProxy = true;
        $translatorWithoutProxy = $this->makeTranslator();
        $translatorWithProxy = $this->makeTranslator([TranslatorOptions::PROXY => $this->proxyUrl]);

        $translatorWithProxy->getUsage();

        $this->expectException(DeepLException::class);
        $translatorWithoutProxy->getUsage();
    }

    public function testUsageNoResponse()
    {
        $this->needsMockServer();
        $this->sessionNoResponse = 2;
        $translator = $this->makeTranslator(
            [TranslatorOptions::MAX_RETRIES => 0, TranslatorOptions::TIMEOUT => 1.0],
        );

        $this->expectException(ConnectionException::class);
        $translator->getUsage();
    }

    public function testUsageNoResponseCustomClient()
    {
        $this->needsMockServer();
        $this->sessionNoResponse = 2;
        $translator = $this->makeTranslator([
            TranslatorOptions::MAX_RETRIES => 0,
            TranslatorOptions::HTTP_CLIENT => new \GuzzleHttp\Client(['timeout' => 1.0])
        ]);

        $this->expectException(ConnectionException::class);
        $translator->getUsage();
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateTooManyRequests(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        $this->session429Count = 2;
        $translator = $this->makeTranslator([
            TranslatorOptions::MAX_RETRIES => 1,
            TranslatorOptions::TIMEOUT => 1.0,
            TranslatorOptions::HTTP_CLIENT => $httpClient,
        ]);

        $this->expectException(TooManyRequestsException::class);
        $translator->translateText(DeepLTestBase::EXAMPLE_TEXT['en'], null, 'de');
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testUsageOverrun(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        $characterLimit = 20;
        $documentLimit = 1;
        $this->sessionInitCharacterLimit = $characterLimit;
        $this->sessionInitDocumentLimit = $documentLimit;

        $translator = $this->makeTranslatorWithRandomAuthKey([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $usage = $translator->getUsage();
        $this->assertFalse($usage->anyLimitReached());
        $this->assertEquals($characterLimit, $usage->character->limit);
        $this->assertEquals($documentLimit, $usage->document->limit);
        $this->assertStringContainsString('Characters: 0 of 20', strval($usage));
        $this->assertStringContainsString('Documents: 0 of 1', strval($usage));

        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        $this->writeFile($exampleDocument, str_repeat('a', $characterLimit));

        $translator->translateDocument($exampleDocument, $outputDocumentPath, null, 'de');

        $usage = $translator->getUsage();
        $this->assertTrue($usage->anyLimitReached());
        $this->assertTrue($usage->character->limitReached());
        $this->assertTrue($usage->document->limitReached());
        $this->assertNull($usage->teamDocument);

        unlink($outputDocumentPath);
        $this->expectException(DocumentTranslationException::class);
        $this->expectExceptionMessage('Quota for this billing period has been exceeded');
        $translator->translateDocument($exampleDocument, $outputDocumentPath, null, "de");
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testUsageTeamDocumentLimit(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        $teamDocumentLimit = 1;
        $this->sessionInitCharacterLimit = 0;
        $this->sessionInitDocumentLimit = 0;
        $this->sessionInitTeamDocumentLimit = $teamDocumentLimit;

        $translator = $this->makeTranslatorWithRandomAuthKey([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $usage = $translator->getUsage();
        $this->assertFalse($usage->anyLimitReached());
        $this->assertNull($usage->character);
        $this->assertNull($usage->document);
        $this->assertFalse($usage->teamDocument->limitReached());
        $this->assertStringNotContainsString("Characters:", strval($usage));
        $this->assertStringNotContainsString("Documents:", strval($usage));
        $this->assertStringContainsString("Team documents:", strval($usage));

        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        $this->writeFile($exampleDocument, 'a');

        $translator->translateDocument($exampleDocument, $outputDocumentPath, null, "de");

        $usage = $translator->getUsage();
        $this->assertTrue($usage->anyLimitReached());
        $this->assertTrue($usage->teamDocument->limitReached());

        unlink($outputDocumentPath);
        $this->expectException(DocumentTranslationException::class);
        $this->expectExceptionMessage('Quota for this billing period has been exceeded');
        $translator->translateDocument($exampleDocument, $outputDocumentPath, null, "de");
    }
}
