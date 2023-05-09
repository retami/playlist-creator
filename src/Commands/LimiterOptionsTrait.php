<?php

declare(strict_types=1);

namespace PlaylistCreator\Commands;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\Exceptions\ParseErrorException;
use Exception;
use PlaylistCreator\Model\Limit;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

trait LimiterOptionsTrait
{
    public const ERROR_LIMIT_AND_DURATION = 'Either duration or limit must be set.';

    public const ERROR_DATE_TIME_FORMAT = 'Invalid format for options date (Y-m-d, e.g. "2023-02-28") '.
    'and/or time (H:i, e.g. "08:00".';

    public const ERROR_DATE_TIME_INVALID = 'Invalid date and/or time.';

    public const ERROR_LIMIT_FORMAT = 'Limit must be a positive integer.';

    public const ERROR_DURATION_FORMAT = 'Duration must be in format G:i, eg. "3:00".';

    public const ERROR_DURATION_LENGTH = 'Duration must be at least 1 minute.';

    private const ARG_NAME_DATE = 'date';

    private const ARG_NAME_TIME = 'time';

    private const ARG_NAME_LIMIT = 'limit';

    private const ARG_NAME_DURATION = 'duration';

    abstract public function addOption(
        string $name,
        mixed $shortcut = null,
        ?int $mode = null,
        string $description = '',
        mixed $default = null
    );

    /**
     * @throws \InvalidArgumentException
     */
    public function getLimit(InputInterface $input): Limit
    {
        $limit = $this->getLimitArgument($input);
        $duration = $this->getDurationArgument($input);
        $datetime = $this->getDateTimeArguments($input);

        if (null === $limit && null === $duration) {
            throw new \InvalidArgumentException(self::ERROR_LIMIT_AND_DURATION);
        }

        return new Limit($datetime, $duration, $limit);
    }

    public function getCarbonDate(string $date, string $time): Carbon
    {
        if ('' === $time) {
            $time = '00:00';
        }

        if ('' === $date) {
            $date = Carbon::now()->format('Y-m-d'); // todo: make testable
        }

        try {
            $datetime = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$time);
        } catch (Exception) {
            throw new \InvalidArgumentException(self::ERROR_DATE_TIME_FORMAT);
        }

        if (false === $datetime) {
            throw new \InvalidArgumentException(self::ERROR_DATE_TIME_FORMAT);
        }

        if ($date !== $datetime->format('Y-m-d') || $time !== $datetime->format('H:i')) {
            throw new \InvalidArgumentException(self::ERROR_DATE_TIME_INVALID);
        }

        return $datetime;
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    private function addLimiterOptions(): self
    {
        $this->addOption(
            self::ARG_NAME_DATE,
            'd',
            InputOption::VALUE_REQUIRED,
            'The date of the songs aired (format Y-m-d, eg. "2022-03-09", default: today).',
            date('Y-m-d')
        )->addOption(
            self::ARG_NAME_TIME,
            't',
            InputOption::VALUE_REQUIRED,
            'The time of the songs aired (format H:i, eg "13:00", default: 00:00).',
            '00:00'
        )->addOption(
            self::ARG_NAME_LIMIT,
            'c',
            InputOption::VALUE_REQUIRED,
            'The number of songs to be added to the playlist. Either duration or limit must be set.',
        )->addOption(
            self::ARG_NAME_DURATION,
            'u',
            InputOption::VALUE_REQUIRED,
            'The duration of the time frame of the broadcast songs to be added to the playlist. '
            .'Either duration or limit must be set.',
        );

        return $this;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getLimitArgument(InputInterface $input): ?int
    {
        $limit = $input->getOption(self::ARG_NAME_LIMIT);

        if (null === $limit) {
            return null;
        }

        if (!is_numeric($limit) || (int) $limit <= 0) {
            throw new \InvalidArgumentException(self::ERROR_LIMIT_FORMAT);
        }

        return (int) $limit;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getDurationArgument(InputInterface $input): ?CarbonInterval
    {
        $duration = $input->getOption(self::ARG_NAME_DURATION);

        if (null === $duration) {
            return null;
        }

        try {
            $interval = CarbonInterval::createFromFormat('G:i', (string) $duration);
        } catch (ParseErrorException) {
            throw new \InvalidArgumentException(self::ERROR_DURATION_FORMAT);
        }

        if (0 === (int) $interval->totalMinutes) {
            throw new \InvalidArgumentException(self::ERROR_DURATION_LENGTH);
        }

        return $interval;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getDateTimeArguments(InputInterface $input): Carbon
    {
        $date = (string) $input->getOption(self::ARG_NAME_DATE);
        $time = (string) $input->getOption(self::ARG_NAME_TIME);

        return $this->getCarbonDate($date, $time);
    }
}
