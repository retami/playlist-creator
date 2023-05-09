<?php

declare(strict_types=1);

namespace PlaylistCreator\Commands;

use PlaylistCreator\Controller\ShowPlaylistController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ShowCommand extends Command
{
    use StationArgumentTrait;
    use LimiterOptionsTrait;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('show')
            ->setDescription('Shows the playlist of aired songs from supported radio stations.')
            ->setHelp('Shows the playlist of aired songs from supported radio stations.')
            ->addStationArgument()
            ->addLimiterOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $extractor = $this->getStation($input);
            $limit = $this->getLimit($input);
        } catch (\InvalidArgumentException $e) {
            $output->writeln('<error>'.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $listController = new ShowPlaylistController($extractor, $output);

        return $listController->list($limit);
    }
}
