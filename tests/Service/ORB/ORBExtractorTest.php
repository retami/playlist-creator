<?php

declare(strict_types=1);

namespace tests\PlaylistCreator\Service\ORB;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use PlaylistCreator\Exception\ExtractException;
use PlaylistCreator\Service\ORB\ORBDomExtractor;
use tests\PlaylistCreator\Helper\FileDomProvider;

class ORBExtractorTest extends TestCase
{
    public function testSongs(): void
    {
        $file = __DIR__.'/../../resources/day0.html';
        $fileDomProvider = new FileDomProvider($file);
        $datetime = Carbon::createFromFormat('Y-m-d H:i:s', '2022-06-07 08:00:00');
        /** @var Carbon $datetime */
        $dom = $fileDomProvider->fetchDOM($datetime);
        $songs = (new ORBDomExtractor())->read($dom, $datetime);

        self::assertEquals(5, count($songs));
    }

    public function testSongsAfterGivenPointInTime(): void
    {
        $file = __DIR__.'/../../resources/day0.html';
        $fileDomProvider = new FileDomProvider($file);
        $datetime = Carbon::createFromFormat('Y-m-d H:i:s', '2022-06-07 09:05:00');
        /** @var Carbon $datetime */
        $dom = $fileDomProvider->fetchDOM($datetime);
        $songs = (new ORBDomExtractor())->read($dom, $datetime);

        self::assertEquals(4, count($songs));
    }

    public function testSongsWithBrokenDOM(): void
    {
        $this->expectException(ExtractException::class);

        $file = __DIR__.'/../../resources/broken_dom.html.html';
        $fileDomProvider = new FileDomProvider($file);
        $datetime = Carbon::createFromFormat('Y-m-d H:i:s', '2022-06-07 08:00:00');
        /** @var Carbon $datetime */
        $dom = $fileDomProvider->fetchDOM($datetime);
        $songs = (new ORBDomExtractor())->read($dom, $datetime);
    }

    public function testSongsWithEmptyDOM(): void
    {
        $file = __DIR__.'/../../resources/empty_dom.html';
        $fileDomProvider = new FileDomProvider($file);
        $datetime = Carbon::createFromFormat('Y-m-d H:i:s', '2022-06-07 09:00:00');
        /** @var Carbon $datetime */
        $dom = $fileDomProvider->fetchDOM($datetime);
        $songs = (new ORBDomExtractor())->read($dom, $datetime);

        self::assertEquals(0, count($songs));
    }
}
