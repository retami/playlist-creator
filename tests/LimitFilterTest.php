<?php

declare(strict_types=1);

namespace tests\PlaylistCreator;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PlaylistCreator\Model\Limit;
use PlaylistCreator\Model\LimitFilter;
use PlaylistCreator\Model\Song;

class LimitFilterTest extends TestCase
{
    public function getToFilterGenerator(): \Generator
    {
        for ($i = 0; $i < 100; ++$i) {
            /** Carbon $date */
            $date = Carbon::createFromFormat('Y-m-d G:i:s', '2022-10-03 12:00:00');
            assert(false !== $date);
            $date->addMinutes(3 * $i);
            yield new Song('artist '.(string) $i, 'title '.(string) $i, $date);
        }
    }

    public function testFilterWithNumberLimitSet(): void
    {
        $callable = function (mixed $value): Carbon {
            /* @var Carbon[] $value */
            return $value['date'];
        };
        $start = Carbon::createFromFormat('Y-m-d G:i:s', '2022-10-03 12:23:00');
        assert(false !== $start);
        try {
            $frame = new Limit($start, null, 15);
        } catch (InvalidArgumentException) {
        }
        assert(isset($frame));
        $filter = new LimitFilter($frame);
        $results = $filter->filter($this->getToFilterGenerator());
        $count = count(iterator_to_array($results));

        self::assertEquals(15, $count);
    }

    public function testFilterWithIntervalLimitSet(): void
    {
        $callable = function (mixed $value): Carbon {
            /* @var Carbon[] $value */
            return $value['date'];
        };
        $start = Carbon::createFromFormat('Y-m-d G:i:s', '2022-10-03 12:23:00');
        assert(false !== $start);
        try {
            $interval = CarbonInterval::create(0, 0, 0, 0, 2);
        } catch (Exception) {
        }
        assert(isset($interval));
        try {
            $frame = new Limit($start, $interval, null);
        } catch (InvalidArgumentException) {
        }
        assert(isset($frame));
        $filter = new LimitFilter($frame);
        $results = $filter->filter($this->getToFilterGenerator());
        $count = count(iterator_to_array($results));

        self::assertEquals(40, $count);
    }
}
