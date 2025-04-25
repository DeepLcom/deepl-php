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

Starting in 2024, we will drop support for older PHP versions that have reached
official end-of-life. You can find the PHP versions and support timelines 
[here][php-version-list].
To continue using this library, you should update to PHP 8.1+.

## Usage

Construct a `DeepLClient` object. The first argument is a string containing your
API authentication key as found in your [DeepL Pro Account][pro-account].

Be careful not to expose your key, for example when sharing source code.

```php
$authKey = "f63c02c5-f056-..."; // Replace with your key
$deeplClient = new \DeepL\DeepLClient($authKey);

$result = $deeplClient->translateText('Hello, world!', null, 'fr');
echo $result->text; // Bonjour, le monde!
```

`DeepLClient` accepts options as the second argument, see
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
corresponding to your input text(s). `TextResult` has the following properties:
- `text` is the translated text,
- `detectedSourceLang` is the detected source language code,
- `billedCharacters` is the number of characters billed for the text.
- `modelTypeUsed` indicates the translation model used, but is `null` unless the
  `model_type` option is specified.

```php
// Translate text into a target language, in this case, French:
$translationResult = $deeplClient->translateText('Hello, world!', 'en', 'fr');
echo $translationResult->text; // 'Bonjour, le monde !'

// Translate multiple texts into British English:
$translations = $deeplClient->translateText(
    ['お元気ですか？', '¿Cómo estás?'],
    null,
    'en-GB',
);
echo $translations[0]->text; // 'How are you?'
echo $translations[0]->detectedSourceLang; // 'ja'
echo $translations[0]->billedCharacters;  // 7 - the number of characters in the source text "お元気ですか？"
echo $translations[1]->text; // 'How are you?'
echo $translations[1]->detectedSourceLang; // 'es'
echo $translations[1]->billedCharacters; // 12 - the number of characters in the source text "¿Cómo estás?"

// Translate into German with less and more Formality:
echo $deeplClient->translateText('How are you?', null, 'de', ['formality' => 'less']); // 'Wie geht es dir?'
echo $deeplClient->translateText('How are you?', null, 'de', ['formality' => 'more']); // 'Wie geht es Ihnen?'
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
-   `context`: specifies additional context to influence translations, that is not
    translated itself. Characters in the `context` parameter are not counted toward billing.
    See the [API documentation][api-docs-context-param] for more information and
    example usage.
-   `glossary`: glossary ID of glossary to use for translation.
-   `model_type`: specifies the type of translation model to use, options are:
    - `'quality_optimized'`: use a translation model that maximizes translation quality, at
                             the cost of response time. This option may be unavailable for
                             some language pairs.
    - `'prefer_quality_optimized'`: use the highest-quality translation model for the given
                                    language pair.
    - `'latency_optimized'`: use a translation model that minimizes response time, at the
                             cost of translation quality.

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

### Rephrasing text

To rephrase text, call `rephraseText()`. The first argument is a string containing the text you want to rephrase, or an array of strings if you want to rephrase multiple texts.

The second argument is the target language code, which is optional. If not provided, the text will be rephrased in its original language.

The last argument is optional and specifies extra rephrasing options, see [Rephrasing options](#rephrasing-options) below.

`rephraseText()` returns a `RephraseTextResult`, or an array of `RephraseTextResult`s corresponding to your input text(s), similar to `translateText()`.

```php
// Rephrase a single text:
$result = $deeplClient->rephraseText('The cat sat on the mat.');
echo $result->text; // Returns a rephrased version

// Rephrase multiple texts:
$results = $deeplClient->rephraseText([
    'The cat sat on the mat.',
    'The dog chased the ball.'
]);
echo $results[0]->text; // First rephrased text
echo $results[1]->text; // Second rephrased text

// Rephrase with specific style:
$result = $deeplClient->rephraseText(
    'The meeting went well.',
    'en-US',
    ['writing_style' => 'business']
);

// Rephrase with specific tone:
$result = $deeplClient->rephraseText(
    'We need to discuss this matter.',
    'en-US',
    ['tone' => 'diplomatic']
);
```

#### Rephrasing options

Provide options to the `rephraseText` function as an associative array, using the following keys:

- `writing_style`: Sets the style for the rephrased text:

  - `'academic'`: Academic writing style
  - `'business'`: Business writing style
  - `'casual'`: Casual writing style
  - `'default'`: Default writing style
  - `'simple'`: Simple writing style
  - `'prefer_academic'`: Use academic style if available, otherwise default
  - `'prefer_business'`: Use business style if available, otherwise default
  - `'prefer_casual'`: Use casual style if available, otherwise default
  - `'prefer_simple'`: Use simple style if available, otherwise default

- `tone`: Sets the tone for the rephrased text:
  - `'confident'`: Confident tone
  - `'default'`: Default tone
  - `'diplomatic'`: Diplomatic tone
  - `'enthusiastic'`: Enthusiastic tone
  - `'friendly'`: Friendly tone
  - `'prefer_confident'`: Use confident tone if available, otherwise default
  - `'prefer_diplomatic'`: Use diplomatic tone if available, otherwise default
  - `'prefer_enthusiastic'`: Use enthusiastic tone if available, otherwise default
  - `'prefer_friendly'`: Use friendly tone if available, otherwise default

Note: Currently, you can only specify either a style OR a tone, not both at the same time.

The `DeepLClientOptions` class defines constants for these options:

- `RephraseTextOptions::WRITING_STYLE`
- `RephraseTextOptions::TONE`

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
    $deeplClient->translateDocument(
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
-   `minification`: A `bool` value. If set to `true`, the library will try to minify a document before translating it through the API, sending a smaller document if the file contains a lot of media. This is currently only supported for `pptx` files. See also [Document minification](#document-minification). Note that this only works in the high-level `translateDocument` method, not `uploadDocument`. However, the behavior can be emulated by creating a new `DocumentMinifier` object and calling the minifier's methods in between.

The `uploadDocument` function also supports these options.

The `TranslateDocumentOptions` class defines constants for the options above,
for example `TranslateDocumentOptions::FORMALITY` is defined as `'formality'`.

#### Document minification

In some contexts, one can end up with large document files (e.g. PowerPoint presentations
or Word files with many contributors, especially in a larger organization). However, the
DeepL API enforces a limit of 30 MB for most of these files (see Usage Limits in the docs).
In the case that most of this size comes from media included in the documents (e.g. images,
videos, animations), document minification can help.
In this case, the library will create a temporary directory to extract the document into,
replace the large media with tiny placeholders, create a minified document, translate that
via the API, and re-insert the original media into the original file. Please note that this
requires a bit of additional (temporary) disk space, we recommend at least 2x the file size
of the document to be translated.

To use document minification, simply pass the option to the `translateDocument` function:

```php
$deeplClient->translateDocument(
    $inFile, $outFile, 'en', 'de', [TranslateDocumentOptions::ENABLE_DOCUMENT_MINIFICATION => true]
);
```

In order to use document minification with the lower-level `uploadDocument`, 
`waitUntilDocumentTranslationComplete` and `downloadDocument` methods as well as other details,
see the `DocumentMinifier` class.

Currently supported document types for minification:

1. `pptx`
2. `docx`

Currently supported media types for minification:

1. `png`
2. `jpg`
3. `jpeg`
4. `emf`
5. `bmp`
6. `tiff`
7. `wdp`
8. `svg`
9. `gif`
10. `mp4`
11. `asf`
12. `avi`
13. `m4v`
14. `mpg`
15. `mpeg`
16. `wmv`
17. `mov`
18. `aiff`
19. `au`
20. `mid`
21. `midi`
22. `mp3`
23. `m4a`
24. `wav`
25. `wma`


### Glossaries

Glossaries allow you to customize your translations using user-defined terms.
Multiple glossaries can be stored with your account, each with a user-specified
name and a uniquely-assigned ID.

### v2 versus v3 glossary APIs

The newest version of the glossary APIs are the `/v3` endpoints, allowing both
editing functionality plus support for multilingual glossaries. New methods and
objects have been created to support interacting with these new glossaries.
Due to this new functionality, users are recommended to utilize these
multilingual glossary methods. However, to continue using the `v2` glossary API
endpoints, please continue to use the existing endpoints in the `Translator.php`
(e.g. `createGlossary()`, `getGlossary()`, etc).

To migrate to use the new multilingual glossary methods from the current
monolingual glossary methods, please refer to
[this migration guide](upgrade_to_multilingual_glossaries.md).

The following sections describe how to interact with multilingual glossaries
using the new functionality:

#### Creating a glossary

You can create a glossary with your desired terms and name using
`createMultilingualGlossary()`. ach glossary contains a list of dictionaries, where each dictionary applies to a single source-target language pair. Note: Glossaries are only supported for some language pairs, see
[Listing available glossary languages](#listing-available-glossary-languages)
for more information. The entries should be specified as a `GlossaryEntries`
object; you can create one using `GlossaryEntries::fromEntries` using an
associative array with the source terms as keys and the target terms as values.

Then use `createMultilingualGlossary()` with the glossary name, and its list of
dictionaries. Each dictionary should have source and target language codes and 
the `GlossaryEntries`. If successful, the glossary is created and stored with 
your DeepL account, and a `MultilingualGlossaryInfo` object is returned
including the ID, name, languages and entry count.

```php
// Create an English to German glossary with two terms:
$entries = GlossaryEntries::fromEntries(['artist' => 'Maler', 'prize' => 'Gewinn']);
$myGlossaryDict = new MultilingualGlossaryDictionaryEntries('en', 'de', $entries);
$myGlossary = $deeplClient->createMultilingualGlossary('My glossary', [$myGlossaryDict]);
echo "Created '$myGlossary->name' ($myGlossary->glossaryId) " .
    "with a dictionary from $myGlossary->dictionaries[0]->sourceLang to " .
    "$myGlossary->dictionaries[0]->targetLang containing " .
    "$myGlossary->dictionaries[0]->entryCount entries";
// Example: Created 'My glossary' (559192ed-8e23-...) with a dictionary from en to de containing 2 entries
```

You can also upload a glossary downloaded from the DeepL website using
`createMultilingualGlossaryFromCsv()`. Specify the glossary
name, and source and target language codes, and instead of specifying the terms
as an associative array, specify the CSV data as a string:

```php
// Read CSV data from a file, for example: "artist,Maler,en,de\nprize,Gewinn,en,de"
$csvData = file_get_contents('/path/to/glossary_file.csv');
$myCsvGlossary = $deeplClient->createMultilingualGlossaryFromCsv(
    'CSV glossary',
    'en',
    'de',
    $csvData,
);
```

The [API documentation][api-docs-csv-format] explains the expected CSV format in
detail.

#### Getting, listing and deleting stored glossaries

Functions to get, list, and delete stored glossaries are also provided:

- `getMultilingualGlossary()` takes a glossary ID and returns a 
  `MultilingualGlossaryInfo` object for a stored glossary, or raises an
  exception if no such glossary is found.
- `listMultilingualGlossaries()` returns a list of `MultilingualGlossaryInfo`
  objects corresponding to all of your stored glossaries.
- `deleteMultilingualGlossary()` takes a glossary ID or 
  `MultilingualGlossaryInfo` object and deletes the stored glossary from the
  server, or raises an exception if no such glossary is found.
- `deleteMultilingualGlossaryDictionary()` takes a glossary ID or
  `MultilingualGlossaryInfo` object to identify the glossary. Additionally
  takes in a source and target language or a
  `MultilingualGlossaryDictionaryInfo` object and deletes the stored dictionary
  from the server, or raises an exception if no such glossary dictionary is
  found.

```php
// Retrieve a stored glossary using the ID
$glossaryId = '559192ed-8e23-...';
$myGlossary = $deeplClient->getMultilingualGlossary($glossaryId);

$deeplClient->deleteMultilingualGlossaryDictionary($glossaryId, $myGlossary->dictionaries[0]);

// Find and delete glossaries named 'Old glossary'
$glossaries = $deeplClient->listMultilingualGlossaries();
foreach ($glossaries as $glossary) {
    if ($glossary->name === 'Old glossary') {
        $deeplClient->deleteMultilingualGlossary($glossary);
    }
}
```

#### Listing entries in a stored glossary

The `MultilingualGlossaryDictionaryInfo` object does not contain the glossary 
entries, but instead only the number of entries in the `entryCount` property.

To list the entries contained within a stored glossary, use
`getMultilingualGlossaryEntries()` providing either the 
`MultilingualGlossaryInfo` object or glossary ID and either a 
`MultilingualGlossaryDictionaryInfo` or source and target language pair:

```php
$glossaryDicts = $deeplClient->getMultilingualGlossaryEntries($myGlossary, "en", "de");
print_r($glossaryDicts[0]->getEntries()); // Array ( [artist] => Maler, [prize] => Gewinn)
```

#### Editing a glossary

Functions to edit stored glossaries are also provided:

- `updateMultilingualGlossary()` takes a glossary ID or 
  `MultilingualGlossaryInfo` object, and optionally an array of 
  `MultilingualGlossaryDictionaryEntries` to update or add to glossary and/or a
  new name for the glossary. For a given glossary dictionary, it will then 
  either update the list of entries for that dictionary (either inserting new 
  entries or replacing the target phrase for any existing entries) or will 
  insert a new glossary dictionary if that language pair is not currently in 
  the stored glossary.
- `replaceMultilingualGlossaryDictionary()` takes a glossary ID or 
  `MultilingualGlossaryInfo` object, plus a 
  `MultilingualGlossaryDictionaryEntries` object representing the updated
  glossary dictionary. It will then either set the dictionary to whatever was
  passed in as a parameter, completely replacing any pre-existing entries for 
  that language pair.

```php
// Update glossary
$entries = GlossaryEntries::fromEntries(['artist' => 'Maler', 'hello' => 'guten tag']);
$myGlossaryDict = new MultilingualGlossaryDictionaryEntries('en', 'de', $entries);
$myGlossary = $deeplClient->createMultilingualGlossary('My glossary', [$myGlossaryDict]);

$newEntries = GlossaryEntries::fromEntries(['hello' => 'hallo', 'prize' => 'Gewinn']);
$myUpdatedGlossaryDict = new MultilingualGlossaryDictionaryEntries('en', 'de', $newEntries);
$myUpdatedGlossary = $deeplClient->updateMultilingualGlossary(
    $myGlossary, 'My updated glossary', [$myUpdatedGlossaryDict]);
print_r($myUpdatedGlossary->dictionaries[0]->getEntries()); // Array ( [artist] => Maler, [hello] => hallo, [prize] => Gewinn)

// Replace glossary dictionary
$entries = GlossaryEntries::fromEntries(['artist' => 'Maler', 'hello' => 'guten tag']);
$myGlossaryDict = new MultilingualGlossaryDictionaryEntries('en', 'de', $entries);
$myGlossary = $deeplClient->createMultilingualGlossary('My glossary', [$myGlossaryDict]);

$newEntries = GlossaryEntries::fromEntries(['hello' => 'hallo', 'prize' => 'Gewinn']);
$myUpdatedGlossaryDictEntries = new MultilingualGlossaryDictionaryEntries('en', 'de', $newEntries);
$myNewGlossaryDict = $deeplClient->replaceMultilingualGlossaryDictionary(
    $myGlossary, $myUpdatedGlossaryDict);
print_r($myNewGlossaryDict->entries->getEntries()); // Array ( [hello] => hallo, [prize] => Gewinn)
```

#### Using a stored glossary

You can use a stored glossary for text translation by setting the `glossary`
option to either the glossary ID or `MultilingualGlossaryInfo` object. You must
also specify the `sourceLang` argument (it is required when using a glossary):

```php
$text = 'The artist was awarded a prize.';
$withGlossary = $deeplClient->translateText($text, 'en', 'de', ['glossary' => $myGlossary]);
echo $withGlossary->text; // "Der Maler wurde mit einem Gewinn ausgezeichnet."

// For comparison, the result without a glossary:
$withoutGlossary = $deeplClient->translateText($text, null, 'de');
echo $withoutGlossary->text; // "Der Künstler wurde mit einem Preis ausgezeichnet."
```

Using a stored glossary for document translation is the same: set the `glossary`
option. The `sourceLang` argument must also be specified:

```php
$deeplClient->translateDocument(
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
$usage = $deeplClient->getUsage();
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

You can request the list of languages supported by DeepL API for text and
documents using the `getSourceLanguages()` and `getTargetLanguages()` functions.
They both return an array of `Language` objects.

The `name` property gives the name of the language in English, and the `code`
property gives the language code. The `supportsFormality` property only appears
for target languages, and is a `bool` indicating whether the target language
supports the optional `formality` parameter.

```php
$sourceLanguages = $deeplClient->getSourceLanguages();
foreach ($sourceLanguages as $sourceLanguage) {
    echo $sourceLanguage->name . ' (' . $sourceLanguage->code . ')'; // Example: 'English (en)'
}

$targetLanguages = $deeplClient->getTargetLanguages();
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
$glossaryLanguages = $deeplClient->getGlossaryLanguages();
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
the `app_info` DeeplClientOption, which needs the name and version of the app:

```php
$options = ['app_info' => new \DeepL\AppInfo('my-custom-php-chat-client', '1.2.3')];
$deeplClient = new \DeepL\DeepLClient('YOUR_AUTH_KEY', $options);
```

This information is passed along when the library makes calls to the DeepL API.
Both name and version are required. Please note that setting the `User-Agent` header
via the `headers` DeeplClientOption will override this setting, if you need to use this,
please manually identify your Application in the `User-Agent` header.

### Configuration

The `DeepLClient` constructor accepts configuration options as a second argument,
for example:

```php
$options = [ 'max_retries' => 5, 'timeout' => 10.0 ];
$deeplClient = new \DeepL\DeepLClient('YOUR_AUTH_KEY', $options);
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

The `DeepLClientOptions` class defines constants for the options above.

#### Proxy configuration

You can configure a proxy using the `proxy` option when constructing a
`DeepLClient`:

```php
$proxy = 'http://user:pass@10.10.1.10:3128';
$deeplClient = new \DeepL\DeepLClient('YOUR_AUTH_KEY', ['proxy' => $proxy]);
```

The proxy option is used for the `CURLOPT_PROXY` option when preparing the cURL
request, see the [documentation for cURL][curl-proxy-docs].

#### Logging

To enable logging, specify a [`PSR-3` compatible logger][PSR-3-logger] as the
`'logger'` option in the `DeepLClient` configuration options.

#### Anonymous platform information

By default, we send some basic information about the platform the client library is running on with each request, see [here for an explanation](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/User-Agent). This data is completely anonymous and only used to improve our product, not track any individual users. If you do not wish to send this data, you can opt-out when creating your `DeepLClient` object by setting the `send_platform_info` flag in the options like so:

```php
$deeplClient = new \DeepL\DeepLClient('YOUR_AUTH_KEY', ['send_platform_info' => false]);
```

You can also customize the `User-Agent` header completely by setting its value explicitly in the options via the `headers` field (this overrides the `send_platform_info` option). For example::

```php
$headers = [
    'Authorization' => "DeepL-Auth-Key YOUR_AUTH_KEY",
    'User-Agent' => 'my-custom-php-client',
];
$deeplClient = new \DeepL\DeepLClient('YOUR_AUTH_KEY', ['headers' => $headers]);
```

### Custom HTTP client

If you want to set specific HTTP options that we don't expose (or otherwise want more control over the API calls by the library), you can configure the library to use a PSR-18 compliant HTTP client of your choosing.
For example, in order to use a connect timeout of 5.2 seconds and read timeout of 7.4 seconds while using a proxy with [Guzzle](https://github.com/guzzle/guzzle):

```php
$client = new \GuzzleHttp\Client([
    'connect_timeout' => 5.2,
    'read_timeout' => 7.4,
    'proxy' => 'http://localhost:8125'
]);
$deeplClient = new \DeepL\DeepLClient('YOUR_AUTH_KEY', [DeepLClientOptions::HTTP_CLIENT => $client]);
$deeplClient->getUsage(); // Or a translate call, etc
```

### Request retries

Requests to the DeepL API that fail due to transient conditions (for example,
network timeouts or high server-load) will be retried. The maximum number of
retries can be configured when constructing the `DeepLClient` object using the
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

### Formatting and fixing code

For formatting and fixing code style issues, run this command:

```bash
./vendor/bin/phpcbf
./vendor/bin/phpcs
```

[api-docs]: https://www.deepl.com/docs-api?utm_source=github&utm_medium=github-php-readme

[api-docs-csv-format]: https://www.deepl.com/docs-api/managing-glossaries/supported-glossary-formats/?utm_source=github&utm_medium=github-php-readme

[api-docs-context-param]: https://www.deepl.com/docs-api/translating-text/?utm_source=github&utm_medium=github-php-readme

[api-docs-glossary-lang-list]: https://www.deepl.com/docs-api/managing-glossaries/?utm_source=github&utm_medium=github-php-readme

[create-account]: https://www.deepl.com/pro?utm_source=github&utm_medium=github-php-readme#developer

[curl-proxy-docs]: https://www.php.net/manual/en/function.curl-setopt.php

[deepl-mock]: https://www.github.com/DeepLcom/deepl-mock

[issues]: https://www.github.com/DeepLcom/deepl-php/issues

[php-version-list]: https://www.php.net/supported-versions.php

[pro-account]: https://www.deepl.com/pro-account/?utm_source=github&utm_medium=github-php-readme

[PSR-3-logger]: http://www.php-fig.org/psr/psr-3/
