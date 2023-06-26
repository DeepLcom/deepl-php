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
