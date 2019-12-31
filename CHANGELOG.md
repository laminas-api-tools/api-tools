# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.4.0 - 2018-05-03

### Added

- [zfcampus/zf-apigility#200](https://github.com/zfcampus/zf-apigility/pull/200) adds support for PHP 7.1 and 7.2.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zfcampus/zf-apigility#200](https://github.com/zfcampus/zf-apigility/pull/200) removes support for HHVM.

### Fixed

- [zfcampus/zf-apigility#194](https://github.com/zfcampus/zf-apigility/pull/194) fixes an issue within `Laminas\ApiTools\Application` whereby it was not
  clearing the "stop propagation" flag prior to triggering new events.

## 1.3.0 - 2016-07-28

### Added

- [zfcampus/zf-apigility#169](https://github.com/zfcampus/zf-apigility/pull/169) adds support for
  version 3 releases of Laminas components, retaining compatibility with
  version 2 releases.
- [zfcampus/zf-apigility#169](https://github.com/zfcampus/zf-apigility/pull/169) adds support in
  `Laminas\ApiTools\Application` for handling PHP 7 `Throwable`s (in addition to
  standard exceptions).

### Deprecated

- Nothing.

### Removed

- [zfcampus/zf-apigility#169](https://github.com/zfcampus/zf-apigility/pull/169) removes support for
  PHP 5.5.
- [zfcampus/zf-apigility#169](https://github.com/zfcampus/zf-apigility/pull/169) removes the
  dependency on rwoverdijk/assetmanager. It now *suggests* one or the other of:
  - rwoverdijk/assetmanager `^1.7` (not yet released)
  - laminas-api-tools/api-tools-asset-manager `^1.0`

### Fixed

- Nothing.
