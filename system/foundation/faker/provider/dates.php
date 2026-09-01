<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Dates extends Base
{
    protected static $century = [
        'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X',
        'XI', 'XII', 'XIII', 'XIV', 'XV', 'XVI', 'XVII', 'XVIII', 'XIX', 'XX', 'XXI',
    ];

    /**
     * Get the maximum timestamp from a given value.
     *
     * @param string|int|\DateTime $max
     *
     * @return int
     */
    protected static function getMaxTimestamp($max = 'now')
    {
        if (is_numeric($max)) {
            return (int) $max;
        }

        return (false !== ($max instanceof \DateTime)) ? $max->getTimestamp() : strtotime(empty($max) ? 'now' : $max);
    }

    /**
     * Generate a random Unix timestamp.
     *
     * @param string|int|\DateTime $max
     *
     * @return int
     */
    public static function unixTime($max = 'now')
    {
        return mt_rand(0, static::getMaxTimestamp($max));
    }

    /**
     * Generate a random DateTime object.
     *
     * @param string|int|\DateTime $max
     *
     * @return \DateTime
     */
    public static function dateTime($max = 'now')
    {
        return new \DateTime('@'.static::unixTime($max));
    }

    /**
     * Generate a random DateTime object for an AD date.
     *
     * @param string|int|\DateTime $max
     *
     * @return \DateTime
     */
    public static function dateTimeAD($max = 'now')
    {
        return new \DateTime('@'.mt_rand(-62135597361, static::getMaxTimestamp($max)));
    }

    /**
     * Generate a random date in ISO 8601 format.
     *
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function iso8601($max = 'now')
    {
        return static::date('Y-m-d\TH:i:sO', $max);
    }

    /**
     * Generate a random date string in the given format.
     *
     * @param string $format
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function date($format = 'Y-m-d', $max = 'now')
    {
        return static::dateTime($max)->format($format);
    }

    /**
     * Generate a random time string in the given format.
     *
     * @param string $format
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function time($format = 'H:i:s', $max = 'now')
    {
        return static::dateTime($max)->format($format);
    }

    /**
     * Generate a random DateTime between two dates.
     *
     * @param string|\DateTime $startDate
     * @param string|\DateTime $endDate
     *
     * @return \DateTime
     */
    public static function dateTimeBetween($startDate = '-30 years', $endDate = 'now')
    {
        $startTimestamp = ($startDate instanceof \DateTime) ? $startDate->getTimestamp() : (new \DateTime($startDate))->getTimestamp();
        $endTimestamp = static::getMaxTimestamp($endDate);

        if ($startTimestamp > $endTimestamp) {
            throw new \InvalidArgumentException('Start date must be anterior to end date.');
        }

        return (new \DateTime('@'.mt_rand($startTimestamp, $endTimestamp)))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
    }

    /**
     * Generate a random DateTime this century.
     *
     * @param string|int|\DateTime $max
     *
     * @return \DateTime
     */
    public static function dateTimeThisCentury($max = 'now')
    {
        return static::dateTimeBetween('-100 year', $max);
    }

    /**
     * Generate a random DateTime this decade.
     *
     * @param string|int|\DateTime $max
     *
     * @return \DateTime
     */
    public static function dateTimeThisDecade($max = 'now')
    {
        return static::dateTimeBetween('-10 year', $max);
    }

    /**
     * Generate a random DateTime this year.
     *
     * @param string|int|\DateTime $max
     *
     * @return \DateTime
     */
    public static function dateTimeThisYear($max = 'now')
    {
        return static::dateTimeBetween('-1 year', $max);
    }

    /**
     * Generate a random DateTime this month.
     *
     * @param string|int|\DateTime $max
     *
     * @return \DateTime
     */
    public static function dateTimeThisMonth($max = 'now')
    {
        return static::dateTimeBetween('-1 month', $max);
    }

    /**
     * Get a random AM/PM string.
     *
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function amPm($max = 'now')
    {
        return static::dateTime($max)->format('a');
    }

    /**
     * Get a random day of the month.
     *
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function dayOfMonth($max = 'now')
    {
        return static::dateTime($max)->format('d');
    }

    /**
     * Get a random day of the week.
     *
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function dayOfWeek($max = 'now')
    {
        return static::dateTime($max)->format('l');
    }

    /**
     * Get a random month number.
     *
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function month($max = 'now')
    {
        return static::dateTime($max)->format('m');
    }

    /**
     * Get a random month name.
     *
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function monthName($max = 'now')
    {
        return static::dateTime($max)->format('F');
    }

    /**
     * Get a random year.
     *
     * @param string|int|\DateTime $max
     *
     * @return string
     */
    public static function year($max = 'now')
    {
        return static::dateTime($max)->format('Y');
    }

    /**
     * Get a random century.
     *
     * @return string
     */
    public static function century()
    {
        return static::randomElement(static::$century);
    }

    /**
     * Get a random timezone identifier.
     *
     * @return string
     */
    public static function timezone()
    {
        return static::randomElement(\DateTimeZone::listIdentifiers());
    }
}
