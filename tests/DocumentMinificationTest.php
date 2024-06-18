<?php

// Copyright 2024 DeepL SE (https://www.deepl.com)
// Use of this source code is governed by an MIT
// license that can be found in the LICENSE file.

namespace DeepL;

use \Psr\Http\Client\ClientInterface;

class DocumentMinificationTest extends DeepLTestBase
{
    protected static $unsupportedTestFile;
    protected static $supportedTestFile;
    protected static $tmpDir;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$unsupportedTestFile = self::getFullPathForTestFile(
            DeepLTestBase::DOC_MINIFICATION_UNSUPPORTED_TEST_FILE
        );
        self::inflateTestFileWithLargeImage(
            self::getFullPathForTestFile(DeepLTestBase::DOC_MINIFICATION_UNSUPPORTED_TEST_TEMPLATE),
            self::$unsupportedTestFile
        );
        self::createDocumentMinificationTestFiles();
        self::$supportedTestFile = self::getFullPathForTestFile(DeepLTestBase::DOC_MINIFICATION_TEST_FILES_MAPPING[
            array_key_first(DeepLTestBase::DOC_MINIFICATION_TEST_FILES_MAPPING)
        ]);
        self::$tmpDir = self::getFullPathForTestFile('tmp_dir');
        mkdir(self::$tmpDir);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::removeDocumentMinificationTestFiles();
        unlink(self::getFullPathForTestFile(DeepLTestBase::DOC_MINIFICATION_UNSUPPORTED_TEST_FILE));
        if (is_dir(self::$tmpDir)) {
            rmdir(self::$tmpDir);
        }
    }

    public function testMinifyDocumentHappyPath()
    {
        $minifier = new DocumentMinifier(self::$tmpDir);
        $minifiedDoc = $minifier->minifyDocument(self::$supportedTestFile, false);
        $fileSize = filesize($minifiedDoc);
        $this->assertLessThan(50000, $fileSize, 'Did not properly minify document (resulting file too large)');
        $this->assertGreaterThan(100, $fileSize, 'Did not properly minify document (resulting file too small)');

        $minifier->recursivelyDeleteDirectory($minifier->getExtractedDocDirectory());
        $minifier->recursivelyDeleteDirectory($minifier->getOriginalMediaDirectory());
        unlink($minifier->getMinifiedDocFile($minifiedDoc));
    }

    public function testDeminifyDocumentHappyPath()
    {
        $outputFile = self::getFullPathForTestFile('example_zip_transformed.zip');
        $minifier = new DocumentMinifier(self::$tmpDir);
        $minifiedFile = $minifier->minifyDocument(self::$unsupportedTestFile, true);
        $minifier->deminifyDocument($minifiedFile, $outputFile, false);

        $inputExtractionDir = self::$tmpDir . '/input_dir';
        $outputExtractionDir = self::$tmpDir . '/output_dir';
        $this->extractZipFileTo(self::$unsupportedTestFile, $inputExtractionDir);
        $this->extractZipFileTo($outputFile, $outputExtractionDir);
        $this->assertDirectoriesAreEqual(
            $inputExtractionDir,
            $outputExtractionDir,
            'Minified + deminified file are not identical! This could happen if a different compression algorithm '
            . 'was used to create the input, but indicates an issue.'
        );

        $minifier->recursivelyDeleteDirectory(self::$tmpDir);
        unlink($outputFile);
    }

    /**
     * @dataProvider provideHttpClient
     */
    public function testMinifyAndTranslateDocuments(?ClientInterface $httpClient)
    {
        $this->needsRealServer();
        $translator = $this->makeTranslator([TranslatorOptions::HTTP_CLIENT => $httpClient]);
        list(, , , $outputDocumentPath) = $this->tempFiles();
        foreach (DeepLTestBase::DOC_MINIFICATION_TEST_FILES_MAPPING as $inflated) {
            $curDoc = self::getFullPathForTestFile($inflated);
            $status = $translator->translateDocument(
                $curDoc,
                $outputDocumentPath,
                'en',
                'de',
                array(TranslateDocumentOptions::ENABLE_DOCUMENT_MINIFICATION => true)
            );
            $this->assertEquals(50000, $status->billedCharacters);
            $this->assertEquals('done', $status->status);
            $this->assertTrue($status->done());
            unlink($outputDocumentPath);
        }
    }
}
