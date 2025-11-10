<?php

// Copyright 2025 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use Psr\Http\Client\ClientInterface;

class StyleRuleTest extends DeepLTestBase
{
    private const DEFAULT_STYLE_ID = 'dca2e053-8ae5-45e6-a0d2-881156e7f4e4';

    /**
     * @dataProvider provideHttpClient
     */
    public function testGetAllStyleRules(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $styleRules = $client->getAllStyleRules(0, 10, true);
        $this->assertIsArray($styleRules);
        $this->assertGreaterThan(0, count($styleRules));
        $this->assertEquals(self::DEFAULT_STYLE_ID, $styleRules[0]->styleId);
        $this->assertEquals('Default Style Rule', $styleRules[0]->name);
        $this->assertNotNull($styleRules[0]->creationTime);
        $this->assertNotNull($styleRules[0]->updatedTime);
        $this->assertEquals('en', $styleRules[0]->language);
        $this->assertEquals(1, $styleRules[0]->version);
        $this->assertNotNull($styleRules[0]->configuredRules);
        $this->assertNotNull($styleRules[0]->customInstructions);
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testGetAllStyleRulesWithoutDetailed(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $styleRules = $client->getAllStyleRules();

        $this->assertIsArray($styleRules);
        $this->assertGreaterThan(0, count($styleRules));
        $this->assertEquals(self::DEFAULT_STYLE_ID, $styleRules[0]->styleId);
        $this->assertNull($styleRules[0]->configuredRules);
        $this->assertNull($styleRules[0]->customInstructions);
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateTextWithStyleId(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        // Note: this test may use the mock server that will not translate the text,
        // therefore we do not check the translated result.
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $exampleText = DeepLTestBase::EXAMPLE_TEXT['de'];

        $result = $client->translateText(
            $exampleText,
            'de',
            'en-US',
            [TranslateTextOptions::STYLE_ID => self::DEFAULT_STYLE_ID]
        );

        $this->assertNotNull($result);
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateTextWithStyleRuleInfo(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        $client = $this->makeDeeplClient([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $styleRules = $client->getAllStyleRules();
        $styleRule = $styleRules[0];
        $exampleText = DeepLTestBase::EXAMPLE_TEXT['de'];

        $result = $client->translateText(
            $exampleText,
            'de',
            'en-US',
            [TranslateTextOptions::STYLE_ID => $styleRule]
        );

        $this->assertNotNull($result);
    }
}
