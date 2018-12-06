<?php

namespace Zenaton\Tasks;

use Cake\Chronos\Chronos;
use Cake\Chronos\ChronosInterface;
use Cake\Chronos\MutableDateTime;
use PHPUnit\Framework\TestCase;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Test\Mock\Event\DummyEvent;

class WaitTest extends TestCase
{
    public function setUp()
    {
        Chronos::setTestNow(Chronos::now());
    }

    public function tearDown()
    {
        Chronos::setTestNow();
    }

    public function testNewInstanceWithoutEvent()
    {
        $wait = new Wait();

        static::assertInstanceOf(Wait::class, $wait);
        static::assertNull($wait->getEvent());
    }

    public function testNewInstanceWithValidEvent()
    {
        $wait = new Wait(DummyEvent::class);

        static::assertInstanceOf(Wait::class, $wait);
        static::assertSame(DummyEvent::class, $wait->getEvent());
    }

    /**
     * @dataProvider getTestNewInstanceWithInvalidEventThrowsAnExceptionData
     */
    public function testNewInstanceWithInvalidEventThrowsAnException($value)
    {
        $this->expectException(ExternalZenatonException::class);

        $wait = new Wait($value);
    }

    public function getTestNewInstanceWithInvalidEventThrowsAnExceptionData()
    {
        yield [new \stdClass()];
        yield [Chronos::now()];
        yield [\DateTime::class];
        yield [12];
        yield ['zenaton'];
    }

    public function testAddingTimeReturnsTheCorrectResult()
    {
        $wait = new Wait();

        $origin = Chronos::now();
        $date = $origin->toMutable();

        $expected = function () use ($origin, $date) {
            return $date->getTimestamp() - $origin->getTimestamp();
        };

        $wait->seconds(32);
        $date->add(new \DateInterval('PT32S'));
        static::assertEquals($expected(), $wait->_getDuration());

        $wait->minutes(12);
        $date->add(new \DateInterval('PT12M'));
        static::assertEquals($expected(), $wait->_getDuration());

        $wait->hours(20);
        $date->add(new \DateInterval('PT20H'));
        static::assertEquals($expected(), $wait->_getDuration());

        $wait->days(2);
        $date->add(new \DateInterval('P2D'));
        static::assertEquals($expected(), $wait->_getDuration());

        $wait->weeks(2);
        $date->add(new \DateInterval('P2W'));
        static::assertEquals($expected(), $wait->_getDuration());

        $wait->months(3);
        $date->add(new \DateInterval('P3M'));
        static::assertEquals($expected(), $wait->_getDuration());

        $wait->years(1);
        $date->add(new \DateInterval('P1Y'));
        static::assertEquals($expected(), $wait->_getDuration());
    }

    public function testGetDurationWithoutAddingTimeReturnsNull()
    {
        $wait = new Wait();

        static::assertNull($wait->_getDuration());
    }

    public function testGetTimestampOrDurationWhenWaitingForATimestamp()
    {
        $wait = new Wait();
        $currentTimestamp = time();
        $targetTimestamp = $currentTimestamp + 3600;

        $wait->timestamp($targetTimestamp);

        static::assertSame([$targetTimestamp, null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenWaitingForAFutureTime()
    {
        $wait = new Wait();
        $date = MutableDateTime::now();
        $date->add(new \DateInterval('PT1H'));

        $wait->at($date->format('H:i:s'));

        static::assertSame([$date->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenWaitingForAPastTime()
    {
        $wait = new Wait();
        $date = MutableDateTime::now();
        $date->sub(new \DateInterval('PT1H'));

        $wait->at($date->format('H:i:s'));

        // Correct $date because wait should target the next day
        $date->add(new \DateInterval('P1D'));

        static::assertSame([$date->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    /**
     * @dataProvider getTestGetTimestampOrDurationWhenUsingWeekDayData
     */
    public function testGetTimestampOrDurationWhenUsingWeekDay($day)
    {
        $date = MutableDateTime::now();
        $date->add(\Cake\Chronos\ChronosInterval::createFromDateString('next '.$day));

        $wait = new Wait();
        $wait->{$day}();

        static::assertSame([$date->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function getTestGetTimestampOrDurationWhenUsingWeekDayData()
    {
        return [
            ['sunday'],
            ['monday'],
            ['tuesday'],
            ['wednesday'],
            ['thursday'],
            ['friday'],
            ['saturday'],
        ];
    }

    public function testGetTimestampOrDurationWhenAlreadyMondayAndWaitingForNextMonday()
    {
        $date = Chronos::create(2018, 12, 3, 11, 00, 00);
        Chronos::setTestNow($date);

        $wait = new Wait();
        $wait->monday();

        $expectedDate = Chronos::create(2018, 12, 10, 11, 00, 00);

        static::assertSame([$expectedDate->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenUsingCurrentWeekDayAndLaterTime()
    {
        $date = Chronos::create(2018, 12, 3, 11, 00, 00);
        Chronos::setTestNow($date);

        $wait = new Wait();
        $wait
            ->monday()
            ->at('13:00')
        ;

        $expectedDate = Chronos::create(2018, 12, 3, 13, 00, 00);

        static::assertSame([$expectedDate->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenUsingCurrentWeekDayAndPastTime()
    {
        $date = Chronos::create(2018, 12, 3, 11, 00, 00);
        Chronos::setTestNow($date);

        $wait = new Wait();
        $wait
            ->monday()
            ->at('9:00')
        ;

        $expectedDate = Chronos::create(2018, 12, 10, 9, 00, 00);

        static::assertSame([$expectedDate->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenUsingCurrentWeekDayAndCurrentTime()
    {
        $date = Chronos::create(2018, 12, 3, 11, 00, 00);
        Chronos::setTestNow($date);

        $wait = new Wait();
        $wait
            ->monday()
            ->at('11:00')
        ;

        $expectedDate = Chronos::create(2018, 12, 10, 11, 00, 00);

        static::assertSame([$expectedDate->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    /**
     * @dataProvider getWaitForCurrentDayData
     */
    public function testGetTimestampOrDurationWhenWaitingForCurrentDayWaitsOneMonth(ChronosInterface $current, $day, ChronosInterface $expected)
    {
        Chronos::setTestNow($current);

        $wait = new Wait();
        $wait
            ->dayOfMonth($day)
        ;

        static::assertSame([$expected->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function getWaitForCurrentDayData()
    {
        yield [Chronos::create(2018, 12, 3, 11, 00, 00), 3, Chronos::create(2019, 1, 3, 11, 00, 00)];
        yield [Chronos::create(2018, 12, 17, 11, 00, 00), 17, Chronos::create(2019, 1, 17, 11, 00, 00)];
        yield [Chronos::create(2018, 02, 15, 11, 00, 00), 31, Chronos::create(2018, 3, 3, 11, 00, 00)];
        yield [Chronos::create(2018, 01, 31, 11, 00, 00), 31, Chronos::create(2018, 2, 28, 11, 00, 00)];
        yield [Chronos::create(2018, 03, 31, 11, 00, 00), 31, Chronos::create(2018, 4, 30, 11, 00, 00)];
    }

    /**
     * @dataProvider getWaitForCurrentDayAtFutureTimeData
     */
    public function testGetTimestampOrDurationWhenWaitingForCurrentDayAtFutureTimeWaitsFewHours(ChronosInterface $current, ChronosInterface $expected)
    {
        Chronos::setTestNow($current);

        $wait = new Wait();
        $wait
            ->dayOfMonth(3)
            ->at('13:00')
        ;

        static::assertSame([$expected->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function getWaitForCurrentDayAtFutureTimeData()
    {
        yield [Chronos::create(2018, 12, 3, 11, 00, 00), Chronos::create(2018, 12, 3, 13, 00, 00)];
    }

    /**
     * @dataProvider getWaitForCurrentDayAtPastTimeData
     */
    public function testGetTimestampOrDurationWhenWaitingForCurrentDayAtPastTimeWaitsOneMonth(ChronosInterface $current, $day, ChronosInterface $expected)
    {
        Chronos::setTestNow($current);

        $wait = new Wait();
        $wait
            ->dayOfMonth($day)
            ->at('9:00')
        ;

        static::assertSame([$expected->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function getWaitForCurrentDayAtPastTimeData()
    {
        yield [Chronos::create(2018, 12, 3, 11, 00, 00), 3, Chronos::create(2019, 1, 3, 9, 00, 00)];
        yield [Chronos::create(2018, 12, 17, 11, 00, 00), 17, Chronos::create(2019, 1, 17, 9, 00, 00)];
        yield [Chronos::create(2018, 02, 15, 11, 00, 00), 15, Chronos::create(2018, 3, 15, 9, 00, 00)];
        yield [Chronos::create(2018, 01, 31, 11, 00, 00), 31, Chronos::create(2018, 2, 28, 9, 00, 00)];
        yield [Chronos::create(2018, 03, 31, 11, 00, 00), 31, Chronos::create(2018, 4, 30, 9, 00, 00)];
    }

    public function testGetTimestampOrDurationWhenWaitingForCurrentDayAtCurrentTimeWaitsOneMonth()
    {
        $date = Chronos::create(2018, 12, 3, 11, 00, 00);
        Chronos::setTestNow($date);

        $wait = new Wait();
        $wait
            ->dayOfMonth(3)
            ->at('11:00')
        ;

        $expected = Chronos::create(2019, 1, 3, 11, 00, 00);

        static::assertSame([$expected->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }
}
