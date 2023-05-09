<?php

declare(strict_types=1);

namespace PlaylistCreator\Commands;

use InvalidArgumentException;
use PlaylistCreator\Interface;
use PlaylistCreator\Service\ORB\ORBService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

trait StationArgumentTrait
{
    public const ERROR_STATION_NOT_SUPPORTED = 'The station %s is not supported.';
    private static string $ARG_NAME_STATION = 'station';

    abstract public function addArgument(
        string $name,
        ?int $mode = null,
        string $description = '',
        mixed $default = null
    );

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException;
     */
    private function addStationArgument(): self
    {
        $this->addArgument(
            self::$ARG_NAME_STATION,
            InputArgument::REQUIRED,
            'The radio station to retrieve broadcast songs.'
        );

        return $this;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getStation(InputInterface $input): Interface\StationInterface
    {
        $station = (string) $input->getArgument(self::$ARG_NAME_STATION);
        try {
            $extractor = (new ORBService())->getStation($station);
        } catch (InvalidArgumentException) {
            $message = sprintf(self::ERROR_STATION_NOT_SUPPORTED, $station).
                ' List of stations:'.PHP_EOL.$this->asTextList(ORBService::getStationNames());
            throw new \InvalidArgumentException($message);
        }

        return $extractor;
    }

    /**
     * @param array<string, string> $stations
     */
    private function asTextList(array $stations): string
    {
        $listing = '';
        foreach ($stations as $key => $station) {
            $listing .= $key.' - '.$station.PHP_EOL;
        }

        return $listing;
    }
}
