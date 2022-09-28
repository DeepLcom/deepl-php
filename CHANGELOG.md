# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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


[1.1.0]: https://github.com/DeepLcom/deepl-php/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/DeepLcom/deepl-php/compare/v0.4.1...v1.0.0
[0.4.1]: https://github.com/DeepLcom/deepl-php/compare/v0.4.0...v0.4.1
[0.4.0]: https://github.com/DeepLcom/deepl-php/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/DeepLcom/deepl-php/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/DeepLcom/deepl-php/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/DeepLcom/deepl-php/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/DeepLcom/deepl-php/releases/tag/v0.1.0
