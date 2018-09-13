<?php

namespace Zenaton\Tasks;

use Cake\Chronos\Chronos;
use Cake\Chronos\Date;
use Cake\Chronos\MutableDate;
use Cake\Chronos\MutableDateTime;
use PHPUnit\Framework\TestCase;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Test\Mock\Event\DummyEvent;

class ZenatonTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        Chronos::setTestNow(Chronos::now());
        MutableDateTime::setTestNow(MutableDateTime::now());
        Date::setTestNow(Date::now());
        MutableDate::setTestNow(MutableDate::now());
    }

    public static function tearDownAfterClass()
    {
        Chronos::setTestNow();
        MutableDateTime::setTestNow();
        Date::setTestNow();
        MutableDate::setTestNow();
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

        static::assertSame(null, $wait->_getDuration());
    }

    public function testGetTimestampOrDurationWhenUsingTimestamp()
    {
        $wait = new Wait();
        $currentTimestamp = time();
        $targetTimestamp = $currentTimestamp + 3600;

        $wait->timestamp($targetTimestamp);

        static::assertSame([$targetTimestamp, null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenUsingAt()
    {
        $wait = new Wait();
        $date = MutableDateTime::now();
        $date->add(new \DateInterval('PT1H'));

        $wait->at($date->format('H:i:s'));

        static::assertSame([$date->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenUsingAtWithPastTimeTargetsNextDay()
    {
        $wait = new Wait();
        $date = MutableDateTime::now();
        $date->sub(new \DateInterval('PT1H'));

        $wait->at($date->format('H:i:s'));

        // Correct $date because wait should target the next day
        $date->add(new \DateInterval('P1D'));

        static::assertSame([$date->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }

    public function testGetTimestampOrDurationWhenUsingDayOfMonth()
    {
        $wait = new Wait();
        $date = Chronos::parse('first day of next month');

        $wait->dayOfMonth(1);

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
        $weekDays = [
            ['sunday'],
            ['monday'],
            ['tuesday'],
            ['wednesday'],
            ['thursday'],
            ['friday'],
            ['saturday'],
        ];

        // Skip current day as the result would be wrong
        $currentDay = (int) date('w');
        unset($weekDays[$currentDay]);

        return $weekDays;
    }

    public function testGetTimestampOrDurationWhenUsingCurrentWeekDayTargetsCurrentDay()
    {
        $date = MutableDateTime::now();
        $day = strtolower($date->format('l'));

        $wait = new Wait();
        $wait->{$day}();

        static::assertSame([$date->getTimestamp(), null], $wait->_getTimestampOrDuration());
    }
}
