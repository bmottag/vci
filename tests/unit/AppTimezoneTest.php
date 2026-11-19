<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Alberta is permanently UTC-6 since Nov 1, 2026.
 *
 * @internal
 */
final class AppTimezoneTest extends CIUnitTestCase
{
    /**
     * @dataProvider datesProvider
     */
    public function testAppTimezoneIsUtcMinusSixAllYear(string $date): void
    {
        $zone = new DateTimeZone((new App())->appTimezone);

        $this->assertSame(-21600, (new DateTimeImmutable($date, $zone))->getOffset());
    }

    public static function datesProvider(): array
    {
        return [
            'day after the last change' => ['2026-11-02 12:00:00'],
            'winter'                    => ['2027-01-15 12:00:00'],
            'summer'                    => ['2027-07-15 12:00:00'],
        ];
    }
}
