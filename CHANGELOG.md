# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)

## [Unreleased]

### Added

### Changed

### Deprecated

### Removed

### Fixed

### Security

## [0.4.0] - 2019-08-23

### Added

* Added a `intent_id` property when dispatching workflows and tasks, sending events to workflows, and
  pausing/resuming/killing workflows.
* Added a `::getContext()` method in `Zenatonable` trait that is able to retrieve the runtime context
  of the workflow or task currently being executed.

### Changed

* Changed `Properties` class to never serialize the `context` property from tasks and workflows.

### Deprecated

### Removed

### Fixed

### Security

## [0.3.4] - 2019-06-05

### Added

* Added `event_data` property when sending event.

### Removed

* Removed dependency `vlucas/phpdotenv`.

## [0.3.3] - 2019-04-16

### Fixed

* Fixed an error when serializing objects having a private `::__clone()` method.

## [0.3.2] - 2019-04-15

### Changed

* Serialization of resources now results in the integer `0` instead of throwing
  an exception in order to be consistent with the behavior of the PHP serialize
  function.

## [0.3.1] - 2019-03-29

### Fixed

* Fixed an issue with the serializer not serializing private properties defined in parent classes of the object to serialize. (#33)
* Fixed an issue with the serializer adding extra properties to objects implementing `Traversable` interface.

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

[Unreleased]: https://github.com/zenaton/zenaton-php/compare/0.4.0...HEAD
[0.4.0]: https://github.com/zenaton/zenaton-php/compare/0.3.4...0.4.0
[0.3.4]: https://github.com/zenaton/zenaton-php/compare/0.3.3...0.3.4
[0.3.3]: https://github.com/zenaton/zenaton-php/compare/0.3.2...0.3.3
[0.3.2]: https://github.com/zenaton/zenaton-php/compare/0.3.1...0.3.2
[0.3.1]: https://github.com/zenaton/zenaton-php/compare/0.3.0...0.3.1
[0.3.0]: https://github.com/zenaton/zenaton-php/compare/0.2.4...0.3.0
