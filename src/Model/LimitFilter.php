<?php

declare(strict_types=1);

namespace PlaylistCreator\Model;

use PlaylistCreator\Interface\LimitFilterable;

final class LimitFilter
{
    public function __construct(private Limit $timeFrame)
    {
    }

    /**
     * @param \Generator<LimitFilterable> $songs
     *
     * @return \Generator<LimitFilterable>
     */
    public function filter(\Generator $songs): \Generator
    {
        $yielded = 1;

        foreach ($songs as $song) {
            if ($this->timeFrame->isLimitExceeded($yielded)) {
                break;
            }

            $date = $song->getDate();

            if ($this->timeFrame->isBeforeStart($date)) {
                continue;
            }

            if ($this->timeFrame->isTimeLimitExceeded($date)) {
                break;
            }

            yield $song;
            ++$yielded;
        }
    }
}
