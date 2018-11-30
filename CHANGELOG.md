# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)

## Unreleased

### Added

* Added this changelog.
* Added unit tests.
* Added PHP CS Fixer config file and run it on all the codebase.

### Changed

* `Client::findWorkflow()` now returns `null` instead of throwing an exception when the workflow cannot be found.
* Using `Zenatonable::dispatch()` on a task outside of a workflow now dispatches the task execution instead of executing it synchronously.

### Fixed

### Deprecated

### Removed

### Security
