# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)

## [Unreleased]

### Added

### Changed

### Deprecated

### Removed

### Fixed

* Fixed an issue with the serializer not serializing private properties defined in parent classes of the object to serialize. (#33) 

### Security

## [0.3.0] - 2019-03-25

### Added

* Added this changelog.
* Added unit tests.
* Added PHP CS Fixer config file and run it on all the codebase.

### Changed

* `Client::findWorkflow()` now returns `null` instead of throwing an exception when the workflow cannot be found.
* Using `Zenatonable::dispatch()` on a task outside of a workflow now dispatches the task execution instead of executing it synchronously.
* Changed `Client::ZENATON_API_URL` constant value to `https://api.zenaton.com/v1`.

### Fixed

* `Client` now properly encode parameters sent in query strings.
* `Zenaton\Workflow\Version` class is now aliased to avoid namespace shadowing bug in PHP 5.6. See <https://bugs.php.net/bug.php?id=66862>.
* `Wait::monday()` when already a monday will now wait for the next monday instead of expiring immediately, except when
  a specific time is set with `::at()` and is not already past. This behavior is also implemented in related methods
  `::tuesday()`, `::wednesday()`, `::thursday()`, `::friday()`, `::saturday()` and `::sunday()`, and also in the
  `::dayOfMonth()` method.

[Unreleased]: https://github.com/zenaton/zenaton-php/compare/0.3.0...HEAD
[0.3.0]: https://github.com/zenaton/zenaton-php/compare/0.2.4...0.3.0
