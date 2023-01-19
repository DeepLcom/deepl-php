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
PHP to interact with the DeepL API. We intend to support all API functions
with the library, though support for new features may be added to the library
after they’re added to the API.

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
    [Listing available languages](#listing-available-languages). Use the
    `prefer_*` options to apply formality if it is available for the target
    language, or otherwise fallback to the default.  
    - `'less'`: use informal language.
    - `'more'`: use formal, more polite language.
    - `'default'`: use default formality.
    - `'prefer_less'`: use informal language if available, otherwise default.
    - `'prefer_more'`: use formal, more polite language if available, otherwise default.
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
-   `non_splitting_tags`: list of XML tags that should not be used to split text
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

### Glossaries

Glossaries allow you to customize your translations using user-defined terms.
Multiple glossaries can be stored with your account, each with a user-specified
name and a uniquely-assigned ID.

#### Creating a glossary

You can create a glossary with your desired terms and name using
`createGlossary()`. Each glossary applies to a single source-target language
pair. Note: Glossaries are only supported for some language pairs, see
[Listing available glossary languages](#listing-available-glossary-languages)
for more information. The entries should be specified as a `GlossaryEntries`
object; you can create one using `GlossaryEntries::fromEntries` using an
associative array with the source terms as keys and the target terms as values.

Then use `createGlossary()` with the glossary name, source and target language
codes and the `GlossaryEntries`. If successful, the glossary is created and
stored with your DeepL account, and a `GlossaryInfo` object is returned
including the ID, name, languages and entry count.

```php
// Create an English to German glossary with two terms:
$entries = GlossaryEntries::fromEntries(['artist' => 'Maler', 'prize' => 'Gewinn']);
$myGlossary = $translator->createGlossary('My glossary', 'en', 'de', $entries);
echo "Created '$myGlossary->name' ($myGlossary->glossaryId) " .
    "$myGlossary->sourceLang to $myGlossary->targetLang " .
    "containing $myGlossary->entryCount entries";
// Example: Created 'My glossary' (559192ed-8e23-...) en to de containing 2 entries
```

You can also upload a glossary downloaded from the DeepL website using
`createGlossaryFromCsv()`. Similar to `createGlossary`, specify the glossary
name, and source and target language codes, but instead of specifying the terms
as an associative array, specify the CSV data as a string:

```php
// Read CSV data from a file, for example: "artist,Maler,en,de\nprize,Gewinn,en,de"
$csvData = file_get_contents('/path/to/glossary_file.csv');
$myCsvGlossary = $translator->createGlossaryFromCsv(
    'CSV glossary',
    'en',
    'de',
    $csvData,
)
```

The [API documentation][api-docs-csv-format] explains the expected CSV format in
detail.

#### Getting, listing and deleting stored glossaries

Functions to get, list, and delete stored glossaries are also provided:

- `getGlossary()` takes a glossary ID and returns a `GlossaryInfo` object for a
  stored glossary, or raises an exception if no such glossary is found.
- `listGlossaries()` returns a list of `GlossaryInfo` objects corresponding to
  all of your stored glossaries.
- `deleteGlossary()` takes a glossary ID or `GlossaryInfo` object and deletes
  the stored glossary from the server, or raises an exception if no such
  glossary is found.

```php
// Retrieve a stored glossary using the ID
$glossaryId = '559192ed-8e23-...';
$myGlossary = $translator->getGlossary($glossaryId);

// Find and delete glossaries named 'Old glossary'
$glossaries = $translator->listGlossaries();
foreach ($glossaries as $glossary) {
    if ($glossary->name === 'Old glossary') {
        $translator->deleteGlossary($glossary);
    }
}
```

#### Listing entries in a stored glossary

The `GlossaryInfo` object does not contain the glossary entries, but instead
only the number of entries in the `entryCount` property.

To list the entries contained within a stored glossary, use
`getGlossaryEntries()` providing either the `GlossaryInfo` object or glossary
ID. A `GlossaryEntries` object is returned; you can access the entries as an
associative array using `getEntries()`:

```php
$entries = $translator->getGlossaryEntries($myGlossary);
print_r($entries->getEntries()); // Array ( [artist] => Maler, [prize] => Gewinn)
```

#### Using a stored glossary

You can use a stored glossary for text translation by setting the `glossary`
option to either the glossary ID or `GlossaryInfo` object. You must also
specify the `sourceLang` argument (it is required when using a glossary):

```php
$text = 'The artist was awarded a prize.';
$withGlossary = $translator->translateText($text, 'en', 'de', ['glossary' => $myGlossary]);
echo $withGlossary->text; // "Der Maler wurde mit einem Gewinn ausgezeichnet."

// For comparison, the result without a glossary:
$withGlossary = $translator->translateText($text, null, 'de');
echo $withoutGlossary->text; // "Der Künstler wurde mit einem Preis ausgezeichnet."
```

Using a stored glossary for document translation is the same: set the `glossary`
option. The `sourceLang` argument must also be specified:

```php
$translator->translateDocument(
    $inFile, $outFile, 'en', 'de', ['glossary' => $myGlossary]
)
```

The `translateDocument()`  and `translateDocumentUpload()` functions both
support the `glossary` argument.

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

#### Listing available glossary languages

Glossaries are supported for a subset of language pairs. To retrieve those
languages use the `getGlossaryLanguages()` function, which returns an array
of `GlossaryLanguagePair` objects. Each has `sourceLang` and `targetLang`
properties indicating that that pair of language codes is supported.

```php
$glossaryLanguages = $translator->getGlossaryLanguages();
foreach ($glossaryLanguages as $glossaryLanguage) {
    echo "$glossaryLanguage->sourceLang to $glossaryLanguage->targetLang";
    // Example: "en to de", "de to en", etc.
}
```

You can also find the list of supported glossary language pairs in the
[API documentation][api-docs-glossary-lang-list].

Note that glossaries work for all target regional-variants: a glossary for the
target language English (`'en'`) supports translations to both American English
(`'en-US'`) and British English (`'en-GB'`).

### Writing a Plugin

If you use this library in an application, please identify the application with
the `app_info` TranslatorOption, which needs the name and version of the app:

```php
$options = ['app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3')];
$translator = new \DeepL\Translator('YOUR_AUTH_KEY', $options);
```

This information is passed along when the library makes calls to the DeepL API.
Both name and version are required. Please note that setting the `User-Agent` header
via the `headers` TranslatorOption will override this setting, if you need to use this,
please manually identify your Application in the `User-Agent` header.

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
- `proxy`: specify a proxy server URL.
- `logger`: specify a [`PSR-3` compatible logger][PSR-3-logger] that the library
    should log messages to.

The `TranslatorOptions` class defines constants for the options above.

#### Proxy configuration

You can configure a proxy using the `proxy` option when constructing a
`Translator`:

```php
$proxy = 'http://user:pass@10.10.1.10:3128';
$translator = new \DeepL\Translator('YOUR_AUTH_KEY', ['proxy' => $proxy]);
```

The proxy option is used for the `CURLOPT_PROXY` option when preparing the cURL
request, see the [documentation for cURL][curl-proxy-docs].

#### Logging

To enable logging, specify a [`PSR-3` compatible logger][PSR-3-logger] as the 
`'logger'` option in the `Translator` configuration options.

#### Anonymous platform information

By default, we send some basic information about the platform the client library is running on with each request, see [here for an explanation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent). This data is completely anonymous and only used to improve our product, not track any individual users. If you do not wish to send this data, you can opt-out when creating your `Translator` object by setting the `send_platform_option` flag in the options like so:

```php
$translator = new \DeepL\Translator('YOUR_AUTH_KEY', ['send_platform_info' => false]);
```

You can also customize the `User-Agent` header completely by setting its value explicitly in the options via the `headers` field (this overrides the `send_platform_option` option). For example::

```php
$headers = [
    'Authorization' => "DeepL-Auth-Key YOUR_AUTH_KEY",
    'User-Agent' => 'my-custom-php-client',
];
$translator = new \DeepL\Translator('YOUR_AUTH_KEY', ['headers' => $headers]);
```

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

We welcome Pull Requests, please read the
[contributing guidelines](CONTRIBUTING.md).

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

[api-docs-csv-format]: https://www.deepl.com/docs-api/managing-glossaries/supported-glossary-formats/?utm_source=github&utm_medium=github-php-readme

[api-docs-glossary-lang-list]: https://www.deepl.com/docs-api/managing-glossaries/?utm_source=github&utm_medium=github-php-readme

[create-account]: https://www.deepl.com/pro?utm_source=github&utm_medium=github-php-readme#developer

[curl-proxy-docs]: https://www.php.net/manual/en/function.curl-setopt.php

[deepl-mock]: https://www.github.com/DeepLcom/deepl-mock

[issues]: https://www.github.com/DeepLcom/deepl-php/issues

[pro-account]: https://www.deepl.com/pro-account/?utm_source=github&utm_medium=github-php-readme

[PSR-3-logger]: http://www.php-fig.org/psr/psr-3/
