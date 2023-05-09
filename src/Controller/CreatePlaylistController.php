<?php

declare(strict_types=1);

namespace PlaylistCreator\Controller;

use PlaylistCreator\Exception\YoutubeException;
use PlaylistCreator\Interface\StationInterface;
use PlaylistCreator\Interface\YoutubeClientInterface;
use PlaylistCreator\Model\Limit;
use PlaylistCreator\Model\LimitFilter;
use PlaylistCreator\Model\PlaylistOptions;
use PlaylistCreator\Model\Song;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class CreatePlaylistController
{
    public function __construct(
        private YoutubeClientInterface $youtubeClient,
        private StationInterface $station,
        private InputInterface $input,
        private OutputInterface $output,
        private QuestionHelper $helper
    ) {
    }

    public function createPlaylist(Limit $timeFrame, PlaylistOptions $playlistOptions, bool $interactive): int
    {
        $defaultTitle = $this->station->getPlaylistPrefix().' playlist '.
            $timeFrame->getStart()->format('Y-m-d H:i:s');
        $playlistId = null;
        $songsAdded = 0;

        try {
            $extractedSongs = $this->getExtractedSongs($timeFrame);
            $playlistId = $this->resolveOrCreatePlaylist($playlistOptions, $defaultTitle);

            foreach ($extractedSongs as $song) {
                $isAdded = $this->addToPlaylist($song, $playlistId, $interactive);
                $songsAdded += $isAdded ? 1 : 0;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->output->writeln('<error>'.$e->getMessage().'</error>');

            for ($previous = $e->getPrevious(); null !== $previous; $previous = $previous->getPrevious()) {
                $this->output->writeln($previous->getMessage());
                $this->output->writeln($previous->getTraceAsString());
            }

            return Command::FAILURE;
        } finally {
            $this->output->writeln(
                PHP_EOL.(string) $songsAdded.' songs added to playlist.'.PHP_EOL.
                (is_string($playlistId) ? 'https://www.youtube.com/playlist?list='.$playlistId : '')
            );
        }
    }

    /**
     * @throws \PlaylistCreator\Exception\YoutubeException
     */
    private function resolveOrCreatePlaylist(PlaylistOptions $playlistOptions, string $defaultTitle): string
    {
        $id = $playlistOptions->id;

        if (null !== $id) {
            if ($this->youtubeClient->hasPlaylist($id)) {
                return $id;
            }

            throw new YoutubeException('Playlist with ID '.$id.' not found');
        }

        $title = $playlistOptions->title ?? $defaultTitle;
        $this->output->writeln('Creating playlist "'.$title.'"');

        return $this->youtubeClient->addPlaylist($title);
    }

    /**
     * @return iterable<Song>
     */
    private function getExtractedSongs(Limit $limit): iterable
    {
        $extractedSongs = $this->station->getAiredSongs($limit->getStart());
        $filter = new LimitFilter($limit);

        /** @var iterable<Song> $songs */
        $songs = $filter->filter($extractedSongs);

        return $songs;
    }

    /**
     * @throws \PlaylistCreator\Exception\YoutubeException
     */
    private function addToPlaylist(Song $song, string $playlistId, bool $interactive): bool
    {
        $this->output->write((string) $song);
        $searchResult = $this->youtubeClient->searchVideoId((string) $song);

        if (null === $searchResult) {
            $this->output->writeln(' -- <fg=red>not found</>');

            return false;
        }

        if ($this->isRejected($searchResult->title, $searchResult->id, $interactive)) {
            return false;
        }

        $this->youtubeClient->insertInPlaylist($searchResult->id, $playlistId);
        $this->output->writeln('<fg=green>done</>');

        return true;
    }

    private function isRejected(string $title, string $id, bool $interactive): bool
    {
        if (!$interactive) {
            $this->output->write(' -- adding "'.$title.'" to playlist... ');

            return false;
        }

        $this->output->write(' -- found "'.$title.'" ( https://www.youtube.com/v?='.$id.' )');
        $question = new ConfirmationQuestion(' -- add (y/n)?');
        $toAdd = $this->helper->ask($this->input, $this->output, $question);

        return !(bool) $toAdd;
    }
}
