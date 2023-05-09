<?php

declare(strict_types=1);

namespace PlaylistCreator\Interface;

use Carbon\Carbon;
use PlaylistCreator\Model\Song;

interface StationInterface
{
    public function getPlaylistPrefix(): string;

    /**
     * @return \Generator<Song>
     */
    public function getAiredSongs(Carbon $start): \Generator;
}
