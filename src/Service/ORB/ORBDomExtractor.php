<?php

declare(strict_types=1);

namespace PlaylistCreator\Service\ORB;

use Carbon\Carbon;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\Node\AbstractNode;
use PHPHtmlParser\Dom\Node\Collection;
use PlaylistCreator\Exception\ExtractException;
use PlaylistCreator\Model\Song;

final class ORBDomExtractor
{
    /**
     * @return array<Song>
     *
     * @throws ExtractException
     */
    public function read(Dom $dom, Carbon $start): array
    {
        try {
            /** @var Collection|null $songNodes */
            $songNodes = $dom->getElementsByClass('track_history_item');
            /** @var Collection|null $broadcastNodes */
            $broadcastNodes = $dom->getElementsByClass('time--schedule');
        } catch (\Throwable $e) {
            throw new ExtractException('Error parsing DOM: '.$e->getMessage());
        }

        if (null === $songNodes && null === $broadcastNodes) {
            return [];
        }

        if (null === $songNodes || null === $broadcastNodes || count($songNodes) !== count($broadcastNodes)) {
            throw new ExtractException('Error parsing DOM: Number of titles and broadcast dates not equal.');
        }

        /** @var array<AbstractNode> $songNodes */
        $songNodes = $songNodes->toArray();

        /** @var array<AbstractNode> $broadcastNodes */
        $broadcastNodes = $broadcastNodes->toArray();

        $count = count($songNodes);
        $songs = [];

        for ($i = $count - 1; $i >= 0; --$i) {
            $song = html_entity_decode(trim(strip_tags($songNodes[$i]->innerhtml)));
            $broadcastString = html_entity_decode(trim(strip_tags($broadcastNodes[$i]->innerhtml)));

            if ('aktuell' === $broadcastString) { // TODO: Fix this
                continue;
            }

            $time = Carbon::createFromFormat('H:i', $broadcastString);

            if (false === $time) {
                throw new ExtractException('Error parsing DOM: Broadcast time not in correct format.');
            }

            $hour = $time->hour;
            $minute = $time->minute;
            $broadcastTime = $start->copy()->setHour($hour)->setMinute($minute);

            if ($broadcastTime->lt($start)) {
                continue;
            }

            $songSplit = explode(' - ', $song, 2);
            if (2 !== count($songSplit)) {
                continue;
            }

            $artist = $songSplit[0];
            $title = $songSplit[1];
            $songs[] = new Song($artist, $title, $broadcastTime);
        }

        return $songs;
    }
}
