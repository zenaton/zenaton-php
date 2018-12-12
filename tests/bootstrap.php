<?php

require __DIR__.'/../vendor/autoload.php';

// Trigger autoloading of `Zenaton\Version` class.
// This is needed because there is a bug related to namespace shadowing in PHP 5.6 (https://bugs.php.net/bug.php?id=66862)
// that will cause fatal errors when the agent library will use the `Zenaton\Version` class.
// Making sure this class is autoloaded at the beginning of the test suite kind of simulate the agent behavior.
\Zenaton\Version::ID;
