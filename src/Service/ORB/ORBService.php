<?php

declare(strict_types=1);

namespace PlaylistCreator\Service\ORB;

class ORBService
{
    /**
     * @var array<string, array<string, string>>|null
     */
    private static ?array $registry = null;

    /**
     * @return array<string, string>
     */
    public static function getStationNames(): array
    {
        $callable = fn (array $array): string => $array['name'];

        return array_map($callable, self::getStationsRegistry());
    }

    public function getStation(string $stationName): ORBStation
    {
        $registry = self::getStationsRegistry();

        $path = $registry[$stationName]['path'] ?? null;
        $name = $registry[$stationName]['name'] ?? null;

        if (null === $path || null === $name) {
            throw new \InvalidArgumentException('Station not supported. Be sure it is correctly defined in stations.json');
        }

        $orbDomProvider = new ORBDomProvider($registry[$stationName]['path']);

        return new ORBStation($orbDomProvider, $registry[$stationName]['name']);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function getStationsRegistry(): array
    {
        if (null === self::$registry) {
            $jsonString = file_get_contents(__DIR__.'/../../../bin/stations.json');
            if (false === $jsonString) {
                throw new \RuntimeException('Could not read stations.json');
            }
            try {
                self::$registry = json_decode($jsonString, true, 5, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new \RuntimeException('Could not parse stations.json');
            }
        }

        return self::$registry;
    }
}
