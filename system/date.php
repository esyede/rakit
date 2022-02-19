<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Date
{
    /**
     * Berisi timetamp terkini.
     *
     * @var int
     */
    protected $timestamp;

    /**
     * Buat instance baru.
     *
     * @param string|int|\System\Date|\DateTime $date
     */
    public function __construct($date = null)
    {
        if ($date === null) {
            $this->timestamp = time();
        } elseif ($date instanceof \DateTime) {
            $this->timestamp = $date->getTimestamp();
        } else {
            if (static::valid($date)) {
                $this->timestamp = $date->timestamp();
            } else {
                if (is_numeric($date)) {
                    $this->timestamp = $date;
                } else {
                    $timestamp = strtotime($date);
                    $this->timestamp = $timestamp ? $timestamp : false;
                }
            }
        }
    }

    /**
     * Buat instance baru.
     *
     * @param string|int|\System\Date|\DateTime $date
     *
     * @return \System\Date
     */
    public static function make($date = null)
    {
        return new self($date);
    }

    /**
     * Ambil tanggal saat ini.
     *
     * @param string $format
     *
     * @return string
     */
    public static function now($format = null)
    {
        $format = is_null($format) ? 'Y-m-d H:i:s' : $format;
        return static::make(null)->format($format);
    }

    /**
     * Ambil timestamp saat ini.
     *
     * @return int
     */
    public function timestamp()
    {
        return $this->timestamp;
    }

    /**
     * Format tanggl.
     *
     * @param string $format
     *
     * @return string
     */
    public function format($format)
    {
        if (! $this->timestamp) {
            throw new \Exception(sprintf(
                'Cannot format an invalid date timestamp: %s (%s)',
                $this->timestamp, gettype($this->timestamp)
            ));
        }

        if (! is_string($format)) {
            throw new \InvalidArgumentException(sprintf(
                'Date format should be a string, %s given.', gettype($format)
            ));
        }

        return date($format, $this->timestamp);
    }

    /**
     * Setel ulang object date.
     *
     * @param string|int|\System\Date|\DateTime $date
     * @param bool                              $clone
     *
     * @return \System\Date
     */
    public function remake($date, $clone = false)
    {
        if (! $this->timestamp) {
            throw new \Exception(sprintf(
                'Cannot remake an invalid date timestamp: %s (%s)',
                $this->timestamp, gettype($this->timestamp)
            ));
        }

        $timestamp = strtotime($date, $this->timestamp);
        $timestamp = $timestamp ? $timestamp : false;

        if ($clone) {
            $cloned = clone $this;
            $cloned->timestamp = $timestamp;

            return $cloned;
        }

        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Format tanggal.
     *
     * @return string
     */
    public function ago()
    {
        $current = time();
        $timestamp = $this->timestamp();

        if (! $timestamp) {
            throw new \Exception(sprintf(
                'Cannot create fuzzy time of an invalid date timestamp: %s (%s)',
                $timestamp, gettype($timestamp)
            ));
        }

        $duration = [60, 60, 24, 7, 4.35, 12, 10];
        $count = count($duration) - 1;

        $units = ['second', 'minute', 'hour', 'day', 'week', 'month', 'year', 'decade'];
        $ago = Lang::line('date.past')->get();

        $diff = $current - $timestamp;

        if ($diff < 0) {
            $diff = abs($diff);
            $ago = Lang::line('date.future')->get();
        }

        for ($i = 0; $diff >= $duration[$i] && $i < $count; $i++) {
            $diff /= $duration[$i];
        }

        $diff = (int) round($diff);

        return number_format($diff).' '.Lang::line('date.'.$units[$i])->get().' '.$ago;
    }

    /**
     * Hitung perbedaan selisih antara dua tanggal.
     *
     * @param string|int|\System\Date $date1
     * @param string|int|\System\Date $date2
     *
     * @return \DateInterval|bool
     */
    public static function diff($date1, $date2 = null)
    {
        $date1 = static::valid($date1) ? $date1 : static::make($date1);
        $date2 = static::valid($date2) ? $date2 : static::make($date2);

        $timestamp1 = $date1->timestamp();
        $timestamp2 = $date2->timestamp();

        if (! $timestamp1 || ! $timestamp2) {
            throw new \Exception(sprintf(
                'Cannot diff an invalid date timestamp, date1: %s (%s). date2: %s (%s)',
                $timestamp1, gettype($timestamp1), $timestamp2, gettype($timestamp2)
            ));
        }

        $date1 = new \DateTime($date1->format('Y-m-d H:i:s'));
        $date2 = new \DateTime($date2->format('Y-m-d H:i:s'));

        return $date1->diff($date2);
    }

    /**
     * Cek apakah tanggal pertama sama dengan tanggal ke-dua.
     *
     * @param string|int|\System\Date|\DateTime $date1
     * @param string|int|\System\Date|\DateTime $date2
     *
     * @return bool
     */
    public static function eq($date1, $date2)
    {
        return static::compare($date1, $date2, 'eq');
    }

    /**
     * Cek apakah tanggal pertama lebih besar dari tanggal ke-dua.
     *
     * @param string|int|\System\Date|\DateTime $date1
     * @param string|int|\System\Date|\DateTime $date2
     *
     * @return bool
     */
    public static function gt($date1, $date2)
    {
        return static::compare($date1, $date2, 'gt');
    }

    /**
     * Cek apakah tanggal pertama lebih kecil dari tanggal ke-dua.
     *
     * @param string|int|\System\Date|\DateTime $date1
     * @param string|int|\System\Date|\DateTime $date2
     *
     * @return bool
     */
    public static function lt($date1, $date2)
    {
        return static::compare($date1, $date2, 'lt');
    }

    /**
     * Cek apakah tanggal pertama lebih besar atau sama dengan tanggal ke-dua.
     *
     * @param string|int|\System\Date|\DateTime $date1
     * @param string|int|\System\Date|\DateTime $date2
     *
     * @return bool
     */
    public static function gte($date1, $date2)
    {
        return static::compare($date1, $date2, 'gte');
    }

    /**
     * Cek apakah tanggal pertama lebih kecil atau sama dengan tanggal ke-dua.
     *
     * @param string|int|\System\Date|\DateTime $date1
     * @param string|int|\System\Date|\DateTime $date2
     *
     * @return bool
     */
    public static function lte($date1, $date2)
    {
        return static::compare($date1, $date2, 'lte');
    }

    /**
     * Bandingkan 2 buah tanggal.
     *
     * @param string|int|\System\Date|\DateTime $date1
     * @param string|int|\System\Date|\DateTime $date2
     * @param string                            $comparator
     *
     * @return bool
     */
    protected static function compare($date1, $date2, $comparator)
    {
        $date1 = static::valid($date1) ? $date1 : static::make($date1);
        $date2 = static::valid($date2) ? $date2 : static::make($date2);

        $timestamp1 = $date1->timestamp();
        $timestamp2 = $date2->timestamp();

        if (! $timestamp1 || ! $timestamp2) {
            throw new \Exception(sprintf(
                'Cannot compare on an invalid date timestamp, date1: %s (%s). date2: %s (%s)',
                $timestamp1, gettype($timestamp1), $timestamp2, gettype($timestamp2)
            ));
        }

        switch ($comparator) {
            case 'eq':  return $timestamp1 === $timestamp2;
            case 'gt':  return $timestamp1 > $timestamp2;
            case 'lt':  return $timestamp1 < $timestamp2;
            case 'gte': return ($timestamp1 > $timestamp2 || $timestamp1 === $timestamp2);
            case 'lte': return ($timestamp1 < $timestamp2 || $timestamp1 === $timestamp2);
            default:    throw new \Exception(sprintf("Invalid date comparator: '%s'", $comparator));
        }
    }

    /**
     * Cek validitas tanggal yang diberikan.
     *
     * @param mixed $date
     *
     * @return bool
     */
    protected static function valid($date)
    {
        return ($date instanceof \System\Date);
    }

    /**
     * Return data dalam bentuk string.
     *
     * @return string
     */
    public function __toString()
    {
        if (! is_numeric($this->timestamp)) {
            throw new \Exception(sprintf(
                'Cannot stringify an invalid date timestamp: %s (%s)',
                $this->timestamp, gettype($this->timestamp)
            ));
        }

        return date('Y-m-d H:i:s', $this->timestamp);
    }
}
