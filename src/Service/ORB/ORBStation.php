<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace PlaylistCreator\Service\ORB;

use Carbon\Carbon;
use JetBrains\PhpStorm\Pure;
use PlaylistCreator\Exception\ExtractException;
use PlaylistCreator\Interface\DomProviderInterface;
use PlaylistCreator\Interface\StationInterface;
use PlaylistCreator\Model\Song;

final class ORBStation implements StationInterface
{
    public const MAX_EMPTY_RESPONSES = 3;

    #[Pure]
    public function __construct(private DomProviderInterface $orbDomProvider, private string $stationName)
    {
    }

    public function getPlaylistPrefix(): string
    {
        return $this->stationName;
    }

    /**
     * @return \Generator<Song>
     *
     * @throws \PlaylistCreator\Exception\ExtractException
     */
    public function getAiredSongs(Carbon $start): \Generator
    {
        $now = Carbon::now();
        $emptyResponses = 0;

        while ($start->lessThan($now)) {
            if (self::MAX_EMPTY_RESPONSES === $emptyResponses) {
                // stop pulling playlists, if the pages load successfully, but no songs are found
                throw new ExtractException('Error fetching songs: Too many empty pages.');
            }

            $dom = $this->orbDomProvider->fetchDOM($start);
            $orbExtractor = new ORBDomExtractor();
            $dailySongs = $orbExtractor->read($dom, $start);

            if (0 === count($dailySongs)) {
                ++$emptyResponses;
            }

            foreach ($dailySongs as $song) {
                yield $song;
            }

            $start = $start->addDay()->setTime(0, 0);
        }

        return null;
    }
}
