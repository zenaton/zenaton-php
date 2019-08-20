<?php
namespace Zenaton\Exceptions;

use PHPUnit\Framework\TestCase;

class ApiExceptionTest extends TestCase
{
    public function testFromErrorList()
    {
        $error1 = new \stdClass();
        $error1->message = 'This is a first error.';

        $error2 = new \stdClass();
        $error2->message = "This is a second error message.\nIt contains a line break because why not?";

        $apiException = ApiException::fromErrorList([$error1, $error2]);

        self::assertSame(<<<EXPECTED
The Zenaton API returned some errors:
  - This is a first error.
  - This is a second error message.
It contains a line break because why not?
EXPECTED,
            $apiException->getMessage()
        );
    }
}
