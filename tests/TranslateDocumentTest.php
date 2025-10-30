<?php

// Copyright 2022 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use \Psr\Http\Client\ClientInterface;

class TranslateDocumentTest extends DeepLTestBase
{
    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateDocument(?ClientInterface $httpClient)
    {
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        $status = $translator->translateDocument($exampleDocument, $outputDocumentPath, 'en', 'de');

        $this->assertEquals(DeepLTestBase::EXAMPLE_DOCUMENT_OUTPUT, $this->readFile($outputDocumentPath));
        $this->assertEquals(strlen(DeepLTestBase::EXAMPLE_DOCUMENT_INPUT), $status->billedCharacters);
        $this->assertEquals('done', $status->status);
        $this->assertTrue($status->done());
    }

    public function testTranslateDocumentWithRetry()
    {
        $this->needsMockServer();
        $this->sessionNoResponse = 1;
        $translator = $this->makeTranslator([TranslatorOptions::TIMEOUT => 0.1]);
        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        $translator->translateDocument($exampleDocument, $outputDocumentPath, 'en', 'de');
        $this->assertEquals(DeepLTestBase::EXAMPLE_DOCUMENT_OUTPUT, $this->readFile($outputDocumentPath));
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateDocumentWithWaiting(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        $this->sessionDocQueueTime = 2.0;
        $this->sessionDocTranslateTime = 2.0;
        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();

        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $translator->translateDocument($exampleDocument, $outputDocumentPath, 'en', 'de');
        $this->assertEquals(DeepLTestBase::EXAMPLE_DOCUMENT_OUTPUT, $this->readFile($outputDocumentPath));
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateLargeDocument(?ClientInterface $httpClient)
    {
        list(, , $exampleLargeDocument, $outputDocumentPath) = $this->tempFiles();

        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $translator->translateDocument($exampleLargeDocument, $outputDocumentPath, 'en', 'de');
        $this->assertEquals($this->EXAMPLE_LARGE_DOCUMENT_OUTPUT, $this->readFile($outputDocumentPath));
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateDocumentFormality(?ClientInterface $httpClient)
    {
        $this->needsRealServer();
        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        $this->writeFile($exampleDocument, 'How are you?');

        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $translator->translateDocument(
            $exampleDocument,
            $outputDocumentPath,
            'en',
            'de',
            [TranslateDocumentOptions::FORMALITY => 'more']
        );
        // Wie geht es Ihnen? uses the formal "Ihnen"
        $this->assertStringContainsString('Ihnen', $this->readFile($outputDocumentPath));

        unlink($outputDocumentPath);
        $translator->translateDocument(
            $exampleDocument,
            $outputDocumentPath,
            'en',
            'de',
            [TranslateDocumentOptions::FORMALITY => 'less']
        );
        // Wie geht es dir? uses the informal "dir"
        $this->assertStringContainsString('dir', $this->readFile($outputDocumentPath));
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateDocumentFailureDuringTranslation(?ClientInterface $httpClient)
    {
        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        $this->writeFile($exampleDocument, DeepLTestBase::EXAMPLE_TEXT['de']);
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);

        // Translating text from DE to DE will trigger error
        try {
            $translator->translateDocument(
                $exampleDocument,
                $outputDocumentPath,
                null,
                'de'
            );
        } catch (DocumentTranslationException $error) {
            $this->assertStringContainsString('Source and target language', $error->getMessage());
            # Ensure that document translation error contains document handle
            $this->assertNotNull($error->handle);
        }
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testInvalidDocument(?ClientInterface $httpClient)
    {
        list($tempDir, , , $outputDocumentPath) = $this->tempFiles();
        $documentPath = $tempDir . '/document.invalid';
        $this->writeFile($documentPath, DeepLTestBase::EXAMPLE_TEXT['en']);
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);

        $this->expectExceptionMessageMatches('/(nvalid file)|(file extension)/');
        $translator->translateDocument($documentPath, $outputDocumentPath, 'en', 'de');
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testTranslateDocumentLowLevel(?ClientInterface $httpClient)
    {
        $this->needsMockServer();
        list(, $exampleDocument, , $outputDocumentPath) = $this->tempFiles();
        // Set a small document queue time to attempt downloading a queued document
        $this->sessionDocQueueTime = 0.2;
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);

        $handle = $translator->uploadDocument($exampleDocument, 'en', 'de');
        $status = $translator->getDocumentStatus($handle);
        $this->assertTrue($status->ok());
        $this->assertFalse($status->done());

        // Test recreating a document handle from id & key
        $handle = new \DeepL\DocumentHandle($handle->documentId, $handle->documentKey);
        $status = $translator->getDocumentStatus($handle);
        $this->assertTrue($status->ok());

        $status = $translator->waitUntilDocumentTranslationComplete($handle);

        $this->assertTrue($status->ok() && $status->done());
        $this->assertEquals(strlen(DeepLTestBase::EXAMPLE_DOCUMENT_INPUT), $status->billedCharacters);
        $translator->downloadDocument($handle, $outputDocumentPath);

        $this->assertEquals(DeepLTestBase::EXAMPLE_DOCUMENT_OUTPUT, $this->readFile($outputDocumentPath));
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testRecreateDocumentHandleInvalid(?ClientInterface $httpClient)
    {
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        $handle = new \DeepL\DocumentHandle(str_repeat('1234', 8), str_repeat('5678', 16));
        $this->expectException(\DeepL\NotFoundException::class);
        $translator->getDocumentStatus($handle);
    }
}
