<?php

declare(strict_types=1);

namespace PlaylistCreator\Model;

use Carbon\Carbon;
use JetBrains\PhpStorm\Pure;
use PlaylistCreator\Interface\LimitFilterable;

final class Song implements \Stringable, LimitFilterable
{
    public function __construct(
        private readonly string $artist,
        private readonly string $title,
        private readonly Carbon $broadcastTime
    ) {
    }

    #[Pure]
    public function __toString(): string
    {
        return "\"{$this->artist}\", \"{$this->title}\"";
    }

    public function getDate(): Carbon
    {
        return $this->broadcastTime;
    }
}
