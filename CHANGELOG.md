# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [Unreleased]


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


[Unreleased]: https://github.com/DeepLcom/deepl-php/compare/v1.4.0...HEAD
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
