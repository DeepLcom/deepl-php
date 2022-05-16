# deepl-php

[![Latest Stable Version](https://img.shields.io/packagist/v/deeplcom/deepl-php.svg)](https://packagist.org/packages/deeplcom/deepl-php)
[![Minimum PHP version](https://img.shields.io/packagist/php-v/deeplcom/deepl-php)](https://packagist.org/packages/deeplcom/deepl-php)
[![License: MIT](https://img.shields.io/badge/license-MIT-blueviolet.svg)](https://github.com/DeepLcom/deepl-php/blob/main/LICENSE)

Official PHP client library for the DeepL API.

The [DeepL API][api-docs] is a language translation API that allows other
computer programs to send texts and documents to DeepL's servers and receive
high-quality translations. This opens a whole universe of opportunities for
developers: any translation product you can imagine can now be built on top of
DeepL's best-in-class translation technology.

The DeepL PHP library offers a convenient way for applications written for
PHP to interact with the DeepL API. Currently, the library only supports text
and document translation; we intend to add support for glossary management soon.

## Getting an authentication key

To use deepl-php, you'll need an API authentication key. To get a key,
[please create an account here][create-account]. With a DeepL API Free account
you can translate up to 500,000 characters/month for free.

## Installation

To use this library in your project, install it using Composer:

```shell
composer require deeplcom/deepl-php
```

### Requirements

The library officially supports PHP 7.3 and later.

## Usage

Construct a `Translator` object. The first argument is a string containing your
API authentication key as found in your [DeepL Pro Account][pro-account].

Be careful not to expose your key, for example when sharing source code.

```php
$authKey = "f63c02c5-f056-..."; // Replace with your key
$translator = new \DeepL\Translator($authKey);

$result = $translator->translateText('Hello, world!', null, 'fr');
echo $result->text; // Bonjour, le monde!
```

`Translator` accepts options as the second argument, see
[Configuration](#configuration) for more information.

### Translating text

To translate text, call `translateText()`. The first argument is a string
containing the text you want to translate, or an array of strings if you want to
translate multiple texts.

The second and third arguments are the source and target language codes.
Language codes are **case-insensitive** strings according to ISO 639-1, for
example `'de'`, `'fr'`, `'ja''`. Some target languages also include the regional
variant according to ISO 3166-1, for example `'en-US'`, or `'pt-BR'`. The source
language also accepts `null`, to enable auto-detection of the source language.

The last argument to `translateText()` is optional, and specifies extra
translation options, see [Text translation options](#text-translation-options)
below.

`translateText()` returns a `TextResult`, or an array of `TextResult`s
corresponding to your input text(s). `TextResult` has two properties: `text` is
the translated text, and `detectedSourceLang` is the detected source language
code.

```php
// Translate text into a target language, in this case, French:
$translationResult = $translator->translateText('Hello, world!', 'en', 'fr');
echo $translationResult->text; // 'Bonjour, le monde !'

// Translate multiple texts into British English:
$translations = $translator->translateText(
    ['お元気ですか？', '¿Cómo estás?'],
    null,
    'en-GB',
);
echo $translations[0]->text; // 'How are you?'
echo $translations[0]->detectedSourceLang; // 'ja'
echo $translations[1]->text; // 'How are you?'
echo $translations[1]->detectedSourceLang; // 'es'

// Translate into German with less and more Formality:
echo $translator->translateText('How are you?', null, 'de', ['formality' => 'less']); // 'Wie geht es dir?'
echo $translator->translateText('How are you?', null, 'de', ['formality' => 'more']); // 'Wie geht es Ihnen?'
```

#### Text translation options

Provide options to the `translateText` function as an associative array,
using the following keys:

-   `split_sentences`: specify how input text should be split into sentences,
    default: `'on'`.
    -   `'on'`: input text will be split into sentences using both newlines and
        punctuation.
    -   `'off'`: input text will not be split into sentences. Use this for
        applications where each input text contains only one sentence.
    -   `'nonewlines'`: input text will be split into sentences using punctuation
        but not newlines.
-   `preserve_formatting`: controls automatic-formatting-correction. Set to `true`
    to prevent automatic-correction of formatting, default: `false`.
-   `formality`: controls whether translations should lean toward informal or
    formal language. This option is only available for some target languages, see
    [Listing available languages](#listing-available-languages).
    -   `'less'`: use informal language.
    -   `'more'`: use formal, more polite language.
-   `tag_handling`: type of tags to parse before translation, options are `'html'`
    and `'xml'`.
-   `glossary`: glossary ID of glossary to use for translation.

The following options are only used if `tag_handling` is `'xml'`:

-   `outline_detection`: specify `false` to disable automatic tag detection,
    default is `true`.
-   `splitting_tags`: list of XML tags that should be used to split text into
    sentences. Tags may be specified as an array of strings (`['tag1', 'tag2']`),
    or a comma-separated list of strings (`'tag1,tag2'`). The default is an empty
    list.
-   `non_splitting_tTags`: list of XML tags that should not be used to split text
    into sentences. Format and default are the same as for `splitting_tags`.
-   `ignore_tags`: list of XML tags that containing content that should not be
    translated. Format and default are the same as for `splitting_tags`.

The `TranslateTextOptions` class defines constants for the options above, for
example `TranslateTextOptions::FORMALITY` is defined as `'formality'`.

### Translating documents

To translate documents, call `translateDocument()`. The first and second
arguments are the input and output file paths.

The third and fourth arguments are the source and target language codes, and
they work exactly the same as when translating text with `translateText()`.

The last argument to `translateDocument()` is optional, and specifies extra
translation options, see
[Document translation options](#document-translation-options) below.

```php
// Translate a formal document from English to German:
try {
    $translator->translateDocument(
        'Instruction Manual.docx',
        'Bedienungsanleitung.docx',
        'en',
        'de',
        ['formality' => 'more'],
    );
} catch (\DeepL\DocumentTranslationException $error) {
    // If the error occurs after the document was already uploaded,
    // documentHandle will contain the document ID and key
    echo 'Error occurred while translating document: ' . ($error->getMessage() ?? 'unknown error');
    if ($error->documentHandle) {
        $handle = $error->documentHandle;
        echo "Document ID: {$handle->documentId}, document key: {$handle->documentKey}";
    } else {
        echo 'Unknown document handle';
    }
}
```

`translateDocument()` wraps multiple API calls: uploading, polling status until
the translation is complete, and downloading. If your application needs to
execute these steps individually, you can instead use the following functions
directly:

-   `uploadDocument()`,
-   `getDocumentStatus()` (or `waitUntilDocumentTranslationComplete()`), and
-   `downloadDocument()`

#### Document translation options

Provide options to the `translateDocument` function as an associative array,
using the following keys:

-   `formality`: same as in [Text translation options](#text-translation-options).
-   `glossary`: same as in [Text translation options](#text-translation-options).

The `uploadDocument` function also supports these options.

The `TranslateDocumentOptions` class defines constants for the options above,
for example `TranslateDocumentOptions::FORMALITY` is defined as `'formality'`.

### Checking account usage

To check account usage, use the `getUsage()` function.

The returned `Usage` object contains up to three usage subtypes, depending on
your account type: `character`, `document` and `teamDocument`. For API accounts
`character` will be set, the others `null`.

Each usage subtypes (if set) have `count` and `limit` properties giving the
amount used and maximum amount respectively, and the `limitReached()` function
that checks if the usage has reached the limit. The top level `Usage` object has
the `anyLimitReached()` function to check all usage subtypes.

```php
$usage = $translator->getUsage();
if ($usage->anyLimitReached()) {
    echo 'Translation limit exceeded.';
}
if ($usage->character) {
    echo 'Characters: ' . $usage->character->count . ' of ' . $usage->character->limit;
}
if ($usage->document) {
    echo 'Documents: ' . $usage->document->count . ' of ' . $usage->document->limit;
}
```

### Listing available languages

You can request the list of languages supported by DeepL Translator for text and
documents using the `getSourceLanguages()` and `getTargetLanguages()` functions.
They both return an array of `Language` objects.

The `name` property gives the name of the language in English, and the `code`
property gives the language code. The `supportsFormality` property only appears
for target languages, and is a `bool` indicating whether the target language
supports the optional `formality` parameter.

```php
$sourceLanguages = $translator->getSourceLanguages();
foreach ($sourceLanguages as $sourceLanguage) {
    echo $sourceLanguage->name . ' (' . $sourceLanguage->code . ')'; // Example: 'English (en)'
}

$targetLanguages = $translator->getTargetLanguages();
foreach ($targetLanguages as $targetLanguage) {
    if ($targetLanguage->supportsFormality) {
        echo $targetLanguage->name . ' (' . $targetLanguage->code . ') supports formality';
        // Example: 'German (de) supports formality'
    }
}
```

### Configuration

The `Translator` constructor accepts configuration options as a second argument,
for example:

```php
$options = [ 'max_retries' => 5, 'timeout' => 10.0 ];
$translator = new \DeepL\Translator('YOUR_AUTH_KEY', $options);
```

Provide the options as an associative array with the following keys: 

- `max_retries`: the maximum number of failed HTTP requests to retry, per
    function call. By default, 5 retries are made. See
    [Request retries](#request-retries).
- `timeout`: the number of seconds used as connection timeout for each
    HTTP request retry. The default value is `10.0` (10 seconds).
- `server_url`: `string` containing the URL of the DeepL API, can be overridden
    for example for testing purposes. By default, the URL is selected based on
    the user account type (free or paid).
- `headers`: extra HTTP headers attached to every HTTP request. By default, no
    extra headers are used. Note that Authorization and User-Agent headers are
    added automatically but may be overridden by this option.
- `logger`: specify a [`PSR-3` compatible logger][PSR-3-logger] that the library
    should log messages to.

The `TranslatorOptions` class defines constants for the options above.

#### Logging

To enable logging, specify a [`PSR-3` compatible logger][PSR-3-logger] as the 
`'logger'` option in the `Translator` configuration options.

### Request retries

Requests to the DeepL API that fail due to transient conditions (for example,
network timeouts or high server-load) will be retried. The maximum number of
retries can be configured when constructing the `Translator` object using the
`max_retries` option. The timeout for each request attempt may be controlled
using the `timeout` option. An exponential-backoff strategy is used, so
requests that fail multiple times will incur delays.

## Issues

If you experience problems using the library, or would like to request a new
feature, please open an [issue][issues].

## Development

We are currently unable to accept Pull Requests. If you would like to suggest
changes, please open an [issue][issues] instead.

### Tests

Execute the tests using `phpunit`. The tests communicate with the DeepL API
using the auth key defined by the `DEEPL_AUTH_KEY` environment variable.

Be aware that the tests make DeepL API requests that contribute toward your API
usage.

The test suite may instead be configured to communicate with the mock-server
provided by [deepl-mock][deepl-mock]. Although most test cases work for either,
some test cases work only with the DeepL API or the mock-server and will be
otherwise skipped. The test cases that require the mock-server trigger server
errors and test the client error-handling. To execute the tests using
deepl-mock, run it in another terminal while executing the tests. Execute the
tests using `phpunit` with the `DEEPL_MOCK_SERVER_PORT` and `DEEPL_SERVER_URL`
environment variables defined referring to the mock-server.


[api-docs]: https://www.deepl.com/docs-api?utm_source=github&utm_medium=github-php-readme

[create-account]: https://www.deepl.com/pro?utm_source=github&utm_medium=github-php-readme#developer

[deepl-mock]: https://www.github.com/DeepLcom/deepl-mock

[issues]: https://www.github.com/DeepLcom/deepl-php/issues

[pro-account]: https://www.deepl.com/pro-account/?utm_source=github&utm_medium=github-php-readme

[PSR-3-logger]: http://www.php-fig.org/psr/psr-3/
