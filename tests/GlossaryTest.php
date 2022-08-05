<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

class GlossaryTest extends DeepLTestBase
{
    private $testEntries = ['Hello' => 'Hallo'];
    private $sourceLang = 'en';
    private $targetLang = 'de';
    private $invalidGlossaryId = 'invalid_glossary_id';
    private $nonexistentGlossaryId = '96ab91fd-e715-41a1-adeb-5d701f84a483';

    private function getGlossaryName(string $testName = ""): string
    {
        $uuid = uniqid();
        return "deepl-php-test-glossary: $testName $uuid";
    }

    private function cleanupGlossary(Translator $translator, string $glossaryName)
    {
        try {
            $glossaries = $translator->listGlossaries();
            foreach ($glossaries as $glossary) {
                if ($glossary->name === $glossaryName) {
                    try {
                        $translator->deleteGlossary($glossary);
                    } catch (DeepLException $exception) {
                        echo "Exception occurred while cleaning up glossary: " . $exception->getMessage() . PHP_EOL;
                    }
                }
            }
        } catch (DeepLException $exception) {
            echo "Exception occurred while listing glossaries for clean up: " . $exception->getMessage() . PHP_EOL;
        }
    }

    public function testGlossaryEntries()
    {
        $this->assertExceptionContains('no entries', function () {
            GlossaryEntries::fromTsv("");
        });
        $this->assertExceptionContains('source term', function () {
            GlossaryEntries::fromTsv("Küche\tKitchen\nKüche\tCuisine");
        });
        $this->assertExceptionContains('term separator', function () {
            GlossaryEntries::fromTsv("A\tB\tC");
        });

        $this->assertExceptionContains('no entries', function () {
            GlossaryEntries::fromEntries([]);
        });
        $this->assertExceptionContains('invalid characters', function () {
            GlossaryEntries::fromEntries(["A" => "B\tC"]);
        });
        $this->assertExceptionContains('invalid characters', function () {
            GlossaryEntries::fromEntries(["A" => "B\u{2028}C"]);
        });
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryCreate()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $entries = GlossaryEntries::fromEntries(['Hello' => 'Hallo']);
            $glossary = $translator->createGlossary($glossaryName, $this->sourceLang, $this->targetLang, $entries);

            $this->assertEquals($glossaryName, $glossary->name);
            $this->assertEquals($this->sourceLang, $glossary->sourceLang);
            $this->assertEquals($this->targetLang, $glossary->targetLang);
            $this->assertEquals(1, $glossary->entryCount);

            $getResult = $translator->getGlossary($glossary->glossaryId);
            $this->assertEquals($glossary->name, $getResult->name);
            $this->assertEquals($glossary->sourceLang, $getResult->sourceLang);
            $this->assertEquals($glossary->targetLang, $getResult->targetLang);
            $this->assertEquals($glossary->creationTime, $getResult->creationTime);
            $this->assertEquals($glossary->entryCount, $getResult->entryCount);
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryCreateLarge()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $entries = [];
            for ($i = 0; $i <10000; $i++) {
                $entries["Source-$i"] = "Target-$i";
            }
            $glossaryEntries = GlossaryEntries::fromEntries($entries);
            $this->assertGreaterThan(100000, strlen($glossaryEntries->convertToTsv()));

            $glossary = $translator->createGlossary(
                $glossaryName,
                $this->sourceLang,
                $this->targetLang,
                $glossaryEntries
            );
            $this->assertEquals($glossaryName, $glossary->name);
            $this->assertEquals($this->sourceLang, $glossary->sourceLang);
            $this->assertEquals($this->targetLang, $glossary->targetLang);
            $this->assertEquals(count($entries), $glossary->entryCount);
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryCreateCsv()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $expectedEntries = ["sourceEntry1" => "targetEntry1", "source\"Entry" => "target,Entry"];
            $csvContent = "sourceEntry1,targetEntry1,en,de\n\"source\"\"Entry\",\"target,Entry\",en,de";
            $glossary = $translator->createGlossaryFromCsv(
                $glossaryName,
                $this->sourceLang,
                $this->targetLang,
                $csvContent
            );
            $this->assertEquals($expectedEntries, $translator->getGlossaryEntries($glossary)->getEntries());
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryCreateInvalid()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $glossaryEntries = GlossaryEntries::fromEntries($this->testEntries);
            $this->assertExceptionContains("name", function () use ($glossaryEntries, $glossaryName, $translator) {
                $translator->createGlossary("", $this->sourceLang, $this->targetLang, $glossaryEntries);
            });
            $this->assertExceptionContains("target", function () use ($glossaryEntries, $glossaryName, $translator) {
                $translator->createGlossary($glossaryName, $this->sourceLang, 'xx', $glossaryEntries);
            });
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryGet()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $glossaryEntries = GlossaryEntries::fromEntries($this->testEntries);
            $createdGlossary = $translator->createGlossary(
                $glossaryName,
                $this->sourceLang,
                $this->targetLang,
                $glossaryEntries
            );

            $glossary = $translator->getGlossary($createdGlossary->glossaryId);
            $this->assertEquals($createdGlossary->glossaryId, $glossary->glossaryId);
            $this->assertEquals($glossaryName, $glossary->name);
            $this->assertEquals($this->sourceLang, $glossary->sourceLang);
            $this->assertEquals($this->targetLang, $glossary->targetLang);
            $this->assertEquals($createdGlossary->creationTime, $glossary->creationTime);
            $this->assertEquals(count($this->testEntries), $glossary->entryCount);

            $this->assertExceptionClass(DeepLException::class, function () use ($translator) {
                $translator->getGlossary($this->invalidGlossaryId);
            });
            $this->assertExceptionClass(GlossaryNotFoundException::class, function () use ($translator) {
                $translator->getGlossary($this->nonexistentGlossaryId);
            });
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryGetEntries()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $entries = [
                'Apple' => 'Apfel', 'Banana' => 'Banane', 'A%=&' => 'B&=%', "\u{0394}\u{3041}" => "\u{6DF1}",
                "\u{1FAA8}" => "\u{1FAB5}"
            ];
            $glossaryEntries = GlossaryEntries::fromEntries($entries);
            $createdGlossary = $translator->createGlossary(
                $glossaryName,
                $this->sourceLang,
                $this->targetLang,
                $glossaryEntries
            );
            $entries = $translator->getGlossaryEntries($createdGlossary)->getEntries();
            print_r($entries);

            $this->assertEquals(
                $glossaryEntries->getEntries(),
                $translator->getGlossaryEntries($createdGlossary)->getEntries()
            );
            $this->assertEquals(
                $glossaryEntries->getEntries(),
                $translator->getGlossaryEntries($createdGlossary->glossaryId)->getEntries()
            );

            $this->assertExceptionClass(DeepLException::class, function () use ($translator) {
                $translator->getGlossaryEntries($this->invalidGlossaryId);
            });
            $this->assertExceptionClass(GlossaryNotFoundException::class, function () use ($translator) {
                $translator->getGlossaryEntries($this->nonexistentGlossaryId);
            });
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryList()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $glossaryEntries = GlossaryEntries::fromEntries($this->testEntries);
            $translator->createGlossary($glossaryName, $this->sourceLang, $this->targetLang, $glossaryEntries);
            $glossaries = $translator->listGlossaries();
            $found = false;
            foreach ($glossaries as $glossary) {
                if ($glossary->name === $glossaryName) {
                    $found = true;
                }
            }
            $this->assertTrue($found);
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryDelete()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $glossaryEntries = GlossaryEntries::fromEntries($this->testEntries);
            $glossary = $translator->createGlossary(
                $glossaryName,
                $this->sourceLang,
                $this->targetLang,
                $glossaryEntries
            );
            $translator->deleteGlossary($glossary);

            $this->assertExceptionClass(GlossaryNotFoundException::class, function () use ($glossary, $translator) {
                $translator->deleteGlossary($glossary);
            });
            $this->assertExceptionClass(DeepLException::class, function () use ($translator) {
                $translator->deleteGlossary($this->invalidGlossaryId);
            });
            $this->assertExceptionClass(GlossaryNotFoundException::class, function () use ($translator) {
                $translator->deleteGlossary($this->nonexistentGlossaryId);
            });
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryTranslateTextSentence()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        try {
            $glossaryEntries = GlossaryEntries::fromEntries(['artist' => 'Maler', 'prize' => 'Gewinn']);
            $input = "The artist was awarded a prize.";
            $glossary = $translator->createGlossary(
                $glossaryName,
                $this->sourceLang,
                $this->targetLang,
                $glossaryEntries
            );
            $result = $translator->translateText(
                $input,
                $this->sourceLang,
                $this->targetLang,
                [TranslateTextOptions::GLOSSARY => $glossary]
            );
            if (!$this->isMockServer) {
                $this->assertStringContainsString('Maler', $result->text);
                $this->assertStringContainsString('Gewinn', $result->text);
            } else {
                // Add an assertion for mock server (phpunit expects an assertion in each test)
                $this->assertEquals($this->sourceLang, $result->detectedSourceLang);
            }

            // Also test using a glossary ID
            $result = $translator->translateText(
                $input,
                $this->sourceLang,
                $this->targetLang,
                [TranslateTextOptions::GLOSSARY => $glossary->glossaryId]
            );
            if (!$this->isMockServer) {
                $this->assertStringContainsString('Maler', $result->text);
                $this->assertStringContainsString('Gewinn', $result->text);
            }
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryTranslateTextBasic()
    {
        $translator = $this->makeTranslator();
        $glossaryNameEnDe = $this->getGlossaryName() . "EnDe";
        $glossaryNameDeEn = $this->getGlossaryName() . "DeEn";
        try {
            $textsEn = ['Apple', 'Banana'];
            $textsDe = ['Apfel', 'Banane'];
            $entriesEnDe = [];
            $entriesDeEn = [];
            for ($i = 0; $i < count($textsDe); $i++) {
                $entriesDeEn[$textsDe[$i]] = $textsEn[$i];
                $entriesEnDe[$textsEn[$i]] = $textsDe[$i];
            }

            $glossaryEnDe = $translator->createGlossary(
                $glossaryNameEnDe,
                'en',
                'de',
                GlossaryEntries::fromEntries($entriesEnDe)
            );
            $glossaryDeEn = $translator->createGlossary(
                $glossaryNameDeEn,
                'de',
                'en',
                GlossaryEntries::fromEntries($entriesDeEn)
            );

            $resultEn = $translator->translateText($textsDe, 'de', 'en-US', ["glossary" => $glossaryDeEn]);
            $resultDe = $translator->translateText($textsEn, 'en', 'de', ["glossary" => $glossaryEnDe]);

            for ($i = 0; $i < count($textsDe); $i++) {
                $this->assertEquals($textsDe[$i], $resultDe[$i]->text);
                $this->assertEquals($textsEn[$i], $resultEn[$i]->text);
            }
        } finally {
            $this->cleanupGlossary($translator, $glossaryNameEnDe);
            $this->cleanupGlossary($translator, $glossaryNameDeEn);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryTranslateDocument()
    {
        $translator = $this->makeTranslator();
        $glossaryName = $this->getGlossaryName();
        $inputText = "artist\nprize";

        $entries = ['artist' => 'Maler', 'prize' => 'Gewinn'];
        $glossaryEntries = GlossaryEntries::fromEntries($entries);
        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        $this->writeFile($exampleDocument, $inputText);

        try {
            $glossary = $translator->createGlossary(
                $glossaryName,
                $this->sourceLang,
                $this->targetLang,
                $glossaryEntries
            );
            $translator->translateDocument(
                $exampleDocument,
                $outputDocumentPath,
                $this->sourceLang,
                $this->targetLang,
                [TranslateDocumentOptions::GLOSSARY => $glossary]
            );
            $this->assertEquals("Maler\nGewinn", $this->readFile($outputDocumentPath));

            unlink($outputDocumentPath);
            $translator->translateDocument(
                $exampleDocument,
                $outputDocumentPath,
                $this->sourceLang,
                $this->targetLang,
                [TranslateDocumentOptions::GLOSSARY => $glossary->glossaryId]
            );
            $this->assertEquals("Maler\nGewinn", $this->readFile($outputDocumentPath));
        } finally {
            $this->cleanupGlossary($translator, $glossaryName);
        }
    }

    /**
     * @throws DeepLException
     */
    public function testGlossaryTranslateTextInvalid()
    {
        $translator = $this->makeTranslator();
        $glossaryNameEnDe = $this->getGlossaryName() . "EnDe";
        $glossaryNameDeEn = $this->getGlossaryName() . "DeEn";
        try {
            $glossaryEnDe = $translator->createGlossary(
                $glossaryNameEnDe,
                'en',
                'de',
                GlossaryEntries::fromEntries($this->testEntries)
            );
            $glossaryDeEn = $translator->createGlossary(
                $glossaryNameDeEn,
                'de',
                'en',
                GlossaryEntries::fromEntries($this->testEntries)
            );

            $this->assertExceptionContains('sourceLang is required', function () use ($glossaryEnDe, $translator) {
                $translator->translateText('test', null, 'de', ['glossary' => $glossaryEnDe]);
            });
            $this->assertExceptionContains(
                'targetLang="en" is deprecated',
                function () use ($glossaryDeEn, $translator) {
                    $translator->translateText('test', 'de', 'en', ['glossary' => $glossaryDeEn]);
                }
            );
        } finally {
            $this->cleanupGlossary($translator, $glossaryNameEnDe);
            $this->cleanupGlossary($translator, $glossaryNameDeEn);
        }
    }
}
