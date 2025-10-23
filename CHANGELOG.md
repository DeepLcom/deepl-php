# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
* Added `extraRequestParameters` option to text and document translation methods to pass arbitrary parameters in the request body. This can be used to access beta features or override built-in parameters (such as `target_lang`, `source_lang`, etc.).

## [1.12.0] - 2025-04-25
### Added
* Added support for the /v3 Multilingual Glossary APIs in the client library 
  while providing backwards compatability for the previous /v2 Glossary 
  endpoints. Please refer to the README or 
  [upgrading_to_multilingual_glossaries.md](upgrading_to_multilingual_glossaries.md)
  for usage instructions.

## [1.11.1] - 2025-01-17
### Fixed
* Fixed `DeepLClientOptions` wrongly inheriting from `TranslateTextOptions`,
  when it should be `TranslatorOptions`.
  * Thanks to [VincentLanglet](https://github.com/VincentLanglet) for the
    [report](https://github.com/DeepLcom/deepl-php/issues/59)


## [1.11.0] - 2025-01-16
### Added
* Added support for the Write API in the client library, the implementation
  can be found in the `DeepLClient` class. Please refer to the README for usage
  instructions.
### Changed
* The main functionality of the library is now also exposed via the `DeepLClient`
  class. Please change your code to use this over the `Translator` class whenever
  convenient.


## [1.10.1] - 2024-11-29
### Fixed
* Fixed the `TextResult` constructor to be compatible with pre-1.10 versions, to
  facilitate mocking
  * Thanks to [VincentLanglet](https://github.com/VincentLanglet) for
    [#56](https://github.com/DeepLcom/deepl-php/pull/56)
* Fixed a bug when generating the platform information with `php_uname`.
  * Thanks to [jochensengier](https://github.com/jochensengier) for
    [#57](https://github.com/DeepLcom/deepl-php/pull/57) and
    [that-guy-iain](https://github.com/that-guy-iain) +
    [jimwins](https://github.com/jimwins) for the
    [report](https://github.com/DeepLcom/deepl-php/issues/54).


## [1.10.0] - 2024-11-15
### Added
* Added `MODEL_TYPE` option to `translateText()` to use models with higher
  translation quality (available for some language pairs), or better latency.
  Options are `'quality_optimized'`, `'latency_optimized'`, and  `'prefer_quality_optimized'`
* Added the `$modelTypeUsed` field to `translateText()` response, that
  indicates the translation model used when the `MODEL_TYPE` option is
  specified.


## [1.9.0] - 2024-09-17
### Added
* Added `$billedCharacters` to the translate text response.


## [1.8.0] - 2024-06-24
### Added
* Added document minification as a feature before document translation, to allow
translation of large `docx` or `pptx` files. For more info check the README.


## [1.7.2] - 2024-04-24
### Fixed
* Added a workaround for rare cases that the DeepL API responds with invalid
  UTF-8 sequences. In these cases the [replacement character][replacement-char]
  "�" (U+FFFD) will replace invalid sequences.
  * Thanks to [VincentLanglet](https://github.com/VincentLanglet) for 
    [#43](https://github.com/DeepLcom/deepl-php/pull/43)


## [1.7.1] - 2024-02-28
### Fixed
* Update `VERSION` values to 1.7.1


## [1.7.0] - 2024-02-27
### Added
* New language available: Arabic (`'ar'`). Add language code constants and tests.
  Arabic is currently supported only for text translation; document translation
  support for Arabic is coming soon.

  Note: older library versions also support the new language, this update only adds new code constants.
### Fixed
* Improve type of `translateText` function in `Translator`
  * Thanks to [VincentLanglet](https://github.com/VincentLanglet) for [#41](https://github.com/DeepLcom/deepl-php/pull/41)


## [1.6.0] - 2023-11-03
### Added
* Add optional `context` parameter for text translation, that specifies
  additional context to influence translations, that is not translated itself.
### Changed
* Added notice in Readme that starting in 2024 the library will drop support for PHP versions that are officially end-of-life.


## [1.5.1] - 2023-09-11
### Fixed
* Add `.gitattributes` file to exclude irrelevant files from package download.
  * Thanks to [VincentLanglet](https://github.com/VincentLanglet) for [#30](https://github.com/DeepLcom/deepl-php/pull/30)
* Internal CI improvements.


## [1.5.0] - 2023-06-26
### Added
* Allow users to supply their own custom HTTP client to the `Translator` object, in order to configure timeouts, security features etc more granularly.
  * Thanks to [VincentLanglet](https://github.com/VincentLanglet) for the good input and work in [#22](https://github.com/DeepLcom/deepl-php/pull/22)
* Add curl version to the platform info in the user-agent header (will not be added if the user opts out).
### Fixed
* Allow users to translate empty strings without throwing an error.
  * Thanks to [VincentLanglet](https://github.com/VincentLanglet) for the work in [#24](https://github.com/DeepLcom/deepl-php/pull/24)
* Catch any exception thrown when computing the user-agent header and continue without failing the request.


## [1.4.0] - 2023-05-24
### Added
* Script to check our source code for license headers and a step for them in the CI.
* Added platform and node version information to the user-agent string that is sent with API calls, along with an opt-out.
* Add method for applications that use this library to identify themselves in API requests they make.
### Fixed
* Fix getUsage request to be a HTTP GET request, not POST.
* Changed document translation to poll the server every 5 seconds. This should greatly reduce observed document translation processing time.


## [1.3.0] - 2023-01-26
### Added
* New languages available: Korean (`'ko'`) and Norwegian (bokmål) (`'nb'`). Add language code constants and tests.

  Note: older library versions also support the new languages, this update only adds new code constants.


## [1.2.1] - 2023-01-25
### Fixed
* Also send options in API requests even if they are default values.


## [1.2.0] - 2022-11-11
### Changed
* Added dependency on `psr/log`. As this package forms a PHP Standard
  Recommendation, we don't consider it to break backward-compatibility.
### Fixed
* Change the type of the `TranslatorOptions::LOGGER` option to 
  `Psr\Log\LoggerInterface`, to correctly support PSR-3 loggers.
  * Pull request [#12](https://github.com/DeepLcom/deepl-php/pull/12)
    thanks to [Schleuse](https://github.com/Schleuse).


## [1.1.0] - 2022-09-28
### Added
* Add new formality options: `'prefer_less'` and `'prefer_more'`.
### Changed
* Requests resulting in `503 Service Unavailable` errors are now retried.
  Attempting to download a document before translation is completed will now
  wait and retry (up to 5 times by default), rather than throwing an exception.


## [1.0.0] - 2022-09-08
Stable release.
### Added
* Add glossary management support.
* New language available: Ukrainian (`'uk'`). Add language code constant and tests.

  Note: older library versions also support new languages, this update only adds new code constant.
* Add proxy support.


## [0.4.1] - 2022-08-12
### Changed
* Update contributing guidelines, we can now accept Pull Requests.
### Fixed
* Fix GitLab CI config.
* Fix a typo in the readme.
  * Pull request [#5](https://github.com/DeepLcom/deepl-php/pull/5)
    thanks to [MartkCz](https://github.com/MartkCz).


## [0.4.0] - 2022-05-24
### Added
* Add support for document translation.


## [0.3.0] - 2022-05-18
### Added
* New languages available: Indonesian (`'id'`) and Turkish (`'tr'`). Add language code constants and tests.

  Note: older library versions also support the new languages, this update only adds new code constants.


## [0.2.0] - 2022-05-02
### Changed
* Remove `final` keyword from class declarations to facilitate testing.


## [0.1.1] - 2022-04-28
### Fixed
* Added minimum supported PHP version to composer.json.
* Fix cURL client issue: do not round timeouts to whole seconds.
* Fix cURL client issue: consider empty response a retryable-error.
* Check for and reject invalid `server_url` option.


## [0.1.0] - 2022-04-22
Initial version.


[1.12.0]: https://github.com/DeepLcom/deepl-php/compare/v1.11.1...v1.12.0
[1.11.1]: https://github.com/DeepLcom/deepl-php/compare/v1.11.0...v1.11.1
[1.11.0]: https://github.com/DeepLcom/deepl-php/compare/v1.10.1...v1.11.0
[1.10.1]: https://github.com/DeepLcom/deepl-php/compare/v1.10.0...v1.10.1
[1.10.0]: https://github.com/DeepLcom/deepl-php/compare/v1.9.0...v1.10.0
[1.9.0]: https://github.com/DeepLcom/deepl-php/compare/v1.8.0...v1.9.0
[1.8.0]: https://github.com/DeepLcom/deepl-php/compare/v1.7.2...v1.8.0
[1.7.2]: https://github.com/DeepLcom/deepl-php/compare/v1.7.1...v1.7.2
[1.7.1]: https://github.com/DeepLcom/deepl-php/compare/v1.7.0...v1.7.1
[1.7.0]: https://github.com/DeepLcom/deepl-php/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/DeepLcom/deepl-php/compare/v1.5.1...v1.6.0
[1.5.1]: https://github.com/DeepLcom/deepl-php/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/DeepLcom/deepl-php/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/DeepLcom/deepl-php/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/DeepLcom/deepl-php/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/DeepLcom/deepl-php/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/DeepLcom/deepl-php/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/DeepLcom/deepl-php/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/DeepLcom/deepl-php/compare/v0.4.1...v1.0.0
[0.4.1]: https://github.com/DeepLcom/deepl-php/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/DeepLcom/deepl-php/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/DeepLcom/deepl-php/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/DeepLcom/deepl-php/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/DeepLcom/deepl-php/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/DeepLcom/deepl-php/releases/tag/v0.1.0

[replacement-char]: https://en.wikipedia.org/wiki/Replacement_character