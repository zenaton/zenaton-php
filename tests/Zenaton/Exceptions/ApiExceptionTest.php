<?php

namespace Zenaton\Exceptions;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversDefaultClass \Zenaton\Exceptions\ApiException
 */
class ApiExceptionTest extends TestCase
{
    /**
     * @covers ::fromErrorList
     */
    public function testFromErrorList()
    {
        $error1 = [
            'message' => 'This is a first error.',
        ];

        $error2 = [
            'message' => "This is a second error message.\nIt contains a line break because why not?",
        ];

        $apiException = ApiException::fromErrorList([$error1, $error2]);
        $expectedMessage = <<<'EXPECTED'
The Zenaton API returned some errors:
  - This is a first error.
  - This is a second error message.
It contains a line break because why not?
EXPECTED;

        self::assertSame($expectedMessage, $apiException->getMessage());
    }

    public function testFromErrorListWithWrongErrorFormat()
    {
        $apiException = ApiException::fromErrorList(['This is a first error.']);
        $expectedMessage = <<<'EXPECTED'
The Zenaton API returned some errors:
array (
  0 => 'This is a first error.',
)
EXPECTED;

        self::assertSame($expectedMessage, $apiException->getMessage());
    }
}
