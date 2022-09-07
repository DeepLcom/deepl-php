<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

class TranslateTextTest extends DeepLTestBase
{
    public function testExampleText()
    {
        $translator = $this->makeTranslator();
        foreach (DeepLTestBase::EXAMPLE_TEXT as $langCode => $exampleText) {
            $sourceLang = \DeepL\LanguageCode::removeRegionalVariant($langCode);
            $result = $translator->translateText($exampleText, $sourceLang, 'en-US');
            $this->assertStringContainsStringIgnoringCase('proton', $result->text, "LangCode: $langCode");
        }
    }

    public function testMultipleText()
    {
        $translator = $this->makeTranslator();
        $input = [DeepLTestBase::EXAMPLE_TEXT['fr'], DeepLTestBase::EXAMPLE_TEXT['en']];
        $result = $translator->translateText($input, null, 'de');
        $this->assertEquals(DeepLTestBase::EXAMPLE_TEXT['de'], $result[0]->text);
        $this->assertEquals('fr', $result[0]->detectedSourceLang);
        $this->assertEquals(DeepLTestBase::EXAMPLE_TEXT['de'], $result[1]->text);
        $this->assertEquals('en', $result[1]->detectedSourceLang);
    }

    public function testLangCodeMixedCase()
    {
        $translator = $this->makeTranslator();
        $result = $translator->translateText(DeepLTestBase::EXAMPLE_TEXT['en'], 'en', 'de');
        $this->assertEquals(DeepLTestBase::EXAMPLE_TEXT['de'], $result->text);
        $this->assertEquals('en', $result->detectedSourceLang);

        $result = $translator->translateText(DeepLTestBase::EXAMPLE_TEXT['en'], 'eN', 'De');
        $this->assertEquals(DeepLTestBase::EXAMPLE_TEXT['de'], $result->text);
        $this->assertEquals('en', $result->detectedSourceLang);
    }

    public function deprecatedTargetLang(): array
    {
        return [['en'], ['pt']];
    }

    /**
     * @dataProvider deprecatedTargetLang
     */
    public function testTargetLangDeprecated(string $targetLang)
    {
        $translator = $this->makeTranslator();
        $this->expectExceptionMessage('deprecated');
        $translator->translateText(DeepLTestBase::EXAMPLE_TEXT['de'], null, $targetLang);
    }

    public function testInvalidSourceLanguage()
    {
        $translator = $this->makeTranslator();
        $this->expectExceptionMessage('source_lang');
        $translator->translateText(DeepLTestBase::EXAMPLE_TEXT['de'], 'xx', 'en-US');
    }

    public function testInvalidTargetLanguage()
    {
        $translator = $this->makeTranslator();
        $this->expectExceptionMessage('target_lang');
        $translator->translateText(DeepLTestBase::EXAMPLE_TEXT['de'], null, 'xx');
    }

    public function invalidTextParameters(): array
    {
        return [[''], [['']]];
    }

    /**
     * @dataProvider invalidTextParameters
     * @param string|string[] $texts
     * @throws DeepLException
     */
    public function testInvalidText($texts)
    {
        $translator = $this->makeTranslator();
        $this->expectExceptionMessage('texts parameter');
        $translator->translateText($texts, null, 'de');
    }

    public function testTranslateWithRetries()
    {
        $this->needsMockServer();
        $this->session429Count = 2;
        $translator = $this->makeTranslator();

        $timeBefore = microtime(true);
        $translator->translateText(DeepLTestBase::EXAMPLE_TEXT['en'], null, 'de');
        $timeAfter = microtime(true);
        // Elapsed time should be at least 1 second
        $this->assertGreaterThan(1.0, $timeAfter - $timeBefore);
    }

    public function testFormality()
    {
        $this->needsRealServer();
        $translator = $this->makeTranslator();
        $input = 'How are you?';
        $formal = 'Wie geht es Ihnen?';
        $informal = 'Wie geht es dir?';

        $this->assertEquals($formal, $translator->translateText($input, null, 'de')->text);
        $this->assertEquals($informal, $translator->translateText(
            $input,
            null,
            'de',
            [TranslateTextOptions::FORMALITY => 'less']
        )->text);
        $this->assertEquals($formal, $translator->translateText(
            $input,
            null,
            'de',
            [TranslateTextOptions::FORMALITY => 'default']
        )->text);
        $this->assertEquals($formal, $translator->translateText(
            $input,
            null,
            'de',
            [TranslateTextOptions::FORMALITY => 'more']
        )->text);

        // Case-insensitive tests
        $this->assertEquals($informal, $translator->translateText(
            $input,
            null,
            'de',
            [TranslateTextOptions::FORMALITY => 'Less']
        )->text);
        $this->assertEquals($formal, $translator->translateText(
            $input,
            null,
            'de',
            [TranslateTextOptions::FORMALITY => 'MORE']
        )->text);

        // prefer_* tests
        $this->assertEquals($informal, $translator->translateText(
            $input,
            null,
            'de',
            [TranslateTextOptions::FORMALITY => 'prefer_less']
        )->text);
        $this->assertEquals($formal, $translator->translateText(
            $input,
            null,
            'de',
            [TranslateTextOptions::FORMALITY => 'prefer_more']
        )->text);
    }

    public function testInvalidFormality()
    {
        $translator = $this->makeTranslator();
        $input = 'How are you?';
        $this->expectExceptionMessage('formality');
        $translator->translateText($input, null, 'de', [TranslateTextOptions::FORMALITY => 'invalid']);
    }

    public function testPreserveFormatting()
    {
        $translator = $this->makeTranslator();
        $input = DeepLTestBase::EXAMPLE_TEXT['en'];
        $translator->translateText($input, null, 'de', [TranslateTextOptions::PRESERVE_FORMATTING => false]);
        $translator->translateText($input, null, 'de', [TranslateTextOptions::PRESERVE_FORMATTING => true]);
        // Add a dummy assertion to avoid warnings
        $this->assertTrue(true);
    }

    public function testSplitSentences()
    {
        $translator = $this->makeTranslator();
        $input = "The firm said it had been\nconducting an internal investigation.";
        $translator->translateText($input, null, 'de', [TranslateTextOptions::SPLIT_SENTENCES => 'off']);
        $translator->translateText($input, null, 'de', [TranslateTextOptions::SPLIT_SENTENCES => 'on']);
        $translator->translateText($input, null, 'de', [TranslateTextOptions::SPLIT_SENTENCES => 'nonewlines']);
        $translator->translateText($input, null, 'de', [TranslateTextOptions::SPLIT_SENTENCES => 'default']);

        // Invalid sentence splitting modes are rejected
        $this->expectExceptionMessage('split_sentences');
        $translator->translateText($input, null, 'de', [TranslateTextOptions::SPLIT_SENTENCES => 'invalid']);
    }

    public function testTagHandlingBasic()
    {
        $translator = $this->makeTranslator();
        $input = "<!DOCTYPE html>\n<html>\n<body>\n<p>This is an example sentence.</p>\n</body>\n</html>";
        // Note: this test may use the mock server that will not translate the text,
        // therefore we do not check the translated result.
        $translator->translateText($input, null, 'de', [TranslateTextOptions::TAG_HANDLING => 'xml']);
        $translator->translateText($input, null, 'de', [TranslateTextOptions::TAG_HANDLING => 'html']);
        // Add a dummy assertion to avoid warnings
        $this->assertTrue(true);
    }

    public function testTagHandlingXML()
    {
        $this->needsRealServer();
        $translator = $this->makeTranslator();

        $text = "
            <document>
                <meta>
                    <title>A document's title</title>
                </meta>
                <content>
                    <par>
                        <span>This is a sentence split</span>
                        <span>across two &lt;span&gt; tags that should be treated as one.
                        </span>
                    </par>
                    <par>Here is a sentence. Followed by a second one.</par>
                    <raw>This sentence will not be translated.</raw>
                </content>
            </document>";

        $result = $translator->translateText(
            $text,
            null,
            'de',
            [TranslateTextOptions::TAG_HANDLING => 'xml',
                TranslateTextOptions::OUTLINE_DETECTION => false,
                TranslateTextOptions::NON_SPLITTING_TAGS => 'span',
                TranslateTextOptions::SPLITTING_TAGS => ['title', 'par'],
                TranslateTextOptions::IGNORE_TAGS => ['raw'],
            ]
        );

        $this->assertStringContainsString('<raw>This sentence will not be translated.</raw>', $result->text);
        $this->assertMatchesRegularExpression('#<title>.*Der Titel.*</title>#', $result->text);
    }

    public function testTagHandlingHTML()
    {
        $this->needsRealServer();
        $translator = $this->makeTranslator();

        $text = '
            <!DOCTYPE html>
            <html>
               <body>
                   <h1>My First Heading</h1>
                   <p translate="no">My first paragraph.</p>
               </body>
            </html>';

        $result = $translator->translateText($text, null, 'de', [TranslateTextOptions::TAG_HANDLING => 'html']);

        $this->assertStringContainsString('<h1>Meine erste Überschrift</h1>', $result->text);
        $this->assertStringContainsString('<p translate="no">My first paragraph.</p>', $result->text);
    }
}
