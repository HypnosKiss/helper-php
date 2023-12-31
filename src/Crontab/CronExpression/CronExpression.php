<?php

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;
use DateTimeZone;
use RuntimeException;

/**
 * CRON expression parser that can determine whether or not a CRON expression is
 * due to run, the next run date and previous run date of a CRON expression.
 * The determinations made by this class are accurate if checked run once per
 * minute (seconds are dropped from date time comparisons).
 * Schedule parts must map to:
 * minute [0-59], hour [0-23], day of month, month [1-12|JAN-DEC], day of week
 * [1-7|MON-SUN], and an optional year.
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:34
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\CronExpression
 * @link   http://en.wikipedia.org/wiki/Cron
 */
class CronExpression
{

    public const MINUTE  = 0;

    public const HOUR    = 1;

    public const DAY     = 2;

    public const MONTH   = 3;

    public const WEEKDAY = 4;

    public const YEAR    = 5;

    /**
     * @var array CRON expression parts
     */
    private $cronParts;

    /**
     * @var FieldFactory CRON field factory
     */
    private $fieldFactory;

    /**
     * @var array Order in which to test of cron parts
     */
    private static $order = [self::YEAR, self::MONTH, self::DAY, self::WEEKDAY, self::HOUR, self::MINUTE];

    /**
     * Factory method to create a new CronExpression.
     * @param string                                                      $expression   The CRON expression to create.  There are
     *                                                                                  several special predefined values which can be used to substitute the
     *                                                                                  CRON expression:
     * @param \Sweeper\HelperPhp\Crontab\CronExpression\FieldFactory|null $fieldFactory (optional) Field factory to use
     * @return CronExpression
     * @throws \Exception
     * @yearly, @annually) - Run once a year, midnight, Jan. 1 - 0 0 1 1 *
     * @monthly - Run once a month, midnight, first of month - 0 0 1 * *
     * @weekly  - Run once a week, midnight on Sun - 0 0 * * 0
     * @daily   - Run once a day, midnight - 0 0 * * *
     * @hourly  - Run once an hour, first minute - 0 * * * *
     */
    public static function factory(string $expression, FieldFactory $fieldFactory = null): self
    {
        $mappings = [
            '@yearly'   => '0 0 1 1 *',
            '@annually' => '0 0 1 1 *',
            '@monthly'  => '0 0 1 * *',
            '@weekly'   => '0 0 * * 0',
            '@daily'    => '0 0 * * *',
            '@hourly'   => '0 * * * *',
        ];

        if (isset($mappings[$expression])) {
            $expression = $mappings[$expression];
        }

        return new self($expression, $fieldFactory ?: new FieldFactory());
    }

    /**
     * Parse a CRON expression
     * @param string       $expression   CRON expression (e.g. '8 * * * *')
     * @param FieldFactory $fieldFactory Factory to create cron fields
     * @throws \Exception
     */
    public function __construct(string $expression, FieldFactory $fieldFactory)
    {
        $this->fieldFactory = $fieldFactory;
        $this->setExpression($expression);
    }

    /**
     * Set or change the CRON expression
     * @param string $value CRON expression (e.g. 8 * * * *)
     * @return CronExpression
     * @throws \Exception if not a valid CRON expression
     */
    public function setExpression(string $value): self
    {
        $this->cronParts = explode(' ', $value);
        if (count($this->cronParts) < 5) {
            throw new \InvalidArgumentException($value . ' is not a valid CRON expression');
        }

        foreach ($this->cronParts as $position => $part) {
            $this->setPart($position, $part);
        }

        return $this;
    }

    /**
     * Set part of the CRON expression
     * @param int    $position The position of the CRON expression to set
     * @param string $value    The value to set
     * @return CronExpression
     * @throws \Exception if the value is not valid for the part
     */
    public function setPart(int $position, string $value): self
    {
        if (!$this->fieldFactory->getField($position)->validate($value)) {
            throw new \InvalidArgumentException('Invalid CRON field value ' . $value . ' as position ' . $position);
        }

        $this->cronParts[$position] = $value;

        return $this;
    }

    /**
     * Get a next run date relative to the current date or a specific date
     * @param string|DateTime $currentTime      (optional) Relative calculation date
     * @param int             $nth              (optional) Number of matches to skip before returning a
     *                                          matching next run date.  0, the default, will return the current
     *                                          date and time if the next run date falls on the current date and
     *                                          time.  Setting this value to 1 will skip the first match and go to
     *                                          the second match.  Setting this value to 2 will skip the first 2
     *                                          matches and so on.
     * @param bool            $allowCurrentDate (optional) Set to TRUE to return the
     *                                          current date if it matches the cron expression
     * @return DateTime
     */
    public function getNextRunDate($currentTime = 'now', int $nth = 0, bool $allowCurrentDate = false): DateTime
    {
        return $this->getRunDate($currentTime, $nth, false, $allowCurrentDate);
    }

    /**
     * Get a previous run date relative to the current date or a specific date
     * @param string|DateTime $currentTime      (optional) Relative calculation date
     * @param int             $nth              (optional) Number of matches to skip before returning
     * @param bool            $allowCurrentDate (optional) Set to TRUE to return the
     *                                          current date if it matches the cron expression
     * @return DateTime
     */
    public function getPreviousRunDate($currentTime = 'now', int $nth = 0, bool $allowCurrentDate = false): DateTime
    {
        return $this->getRunDate($currentTime, $nth, true, $allowCurrentDate);
    }

    /**
     * Get multiple run dates starting at the current date or a specific date
     * @param int             $total            Set the total number of dates to calculate
     * @param string|DateTime $currentTime      (optional) Relative calculation date
     * @param bool            $invert           (optional) Set to TRUE to retrieve previous dates
     * @param bool            $allowCurrentDate (optional) Set to TRUE to return the
     *                                          current date if it matches the cron expression
     * @return array Returns an array of run dates
     */
    public function getMultipleRunDates(int $total, $currentTime = 'now', bool $invert = false, bool $allowCurrentDate = false): array
    {
        $matches = [];
        for ($i = 0; $i < max(0, $total); $i++) {
            $matches[] = $this->getRunDate($currentTime, $i, $invert, $allowCurrentDate);
        }

        return $matches;
    }

    /**
     * Get all or part of the CRON expression
     * @param string|null $part (optional) Specify the part to retrieve or NULL to
     *                          get the full cron schedule string.
     * @return string|null Returns the CRON expression, a part of the
     *                          CRON expression, or NULL if the part was specified but not found
     */
    public function getExpression(string $part = null): ?string
    {
        if ($part === null) {
            return implode(' ', $this->cronParts);
        }

        if (array_key_exists($part, $this->cronParts)) {
            return $this->cronParts[$part];
        }

        return null;
    }

    /**
     * Helper method to output the full expression.
     * @return string Full CRON expression
     */
    public function __toString()
    {
        return $this->getExpression() ?: '';
    }

    /**
     * Deterime if the cron is due to run based on the current date or a
     * specific date.  This method assumes that the current number of
     * seconds are irrelevant, and should be called once per minute.
     * @param null $currentTime (optional) Relative calculation date
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     * @throws \Exception
     */
    public function isDue($currentTime = null): bool
    {
        if ($currentTime === null || $currentTime === 'now') {
            $currentDate = date('Y-m-d H:i');
            $currentTime = strtotime($currentDate);
        } else {
            if ($currentTime instanceof DateTime) {
                $currentDate = $currentTime->format('Y-m-d H:i');
                $currentTime = strtotime($currentDate);
            } else {
                $currentTime = new DateTime($currentTime);
                $currentTime->setTime($currentTime->format('H'), $currentTime->format('i'), 0);
                $currentDate = $currentTime->format('Y-m-d H:i');
                $currentTime = $currentTime->getTimestamp();
            }
        }

        return $this->getNextRunDate($currentDate, 0, true)->getTimestamp() == $currentTime;
    }

    /**
     * Get the next or previous run date of the expression relative to a date
     * @param null $currentTime                 (optional) Relative calculation date
     * @param int  $nth                         (optional) Number of matches to skip before returning
     * @param bool $invert                      (optional) Set to TRUE to go backwards in time
     * @param bool $allowCurrentDate            (optional) Set to TRUE to return the
     *                                          current date if it matches the cron expression
     * @return DateTime
     * @throws \Exception
     */
    protected function getRunDate($currentTime = null, int $nth = 0, bool $invert = false, bool $allowCurrentDate = false): DateTime
    {
        $currentDate = $currentTime instanceof DateTime ? $currentTime : new DateTime($currentTime ?: 'now');

        // set the timezone
        $currentDate->setTimezone(new DateTimeZone(date_default_timezone_get()));

        $currentDate->setTime($currentDate->format('H'), $currentDate->format('i'), 0);
        $nextRun = clone $currentDate;
        $nth     = (int)$nth;

        // Set a hard limit to bail on an impossible date
        for ($i = 0; $i < 1000; $i++) {

            foreach (self::$order as $position) {
                $part = $this->getExpression($position);
                if ($part === null) {
                    continue;
                }

                $satisfied = false;
                // Get the field object used to validate this part
                $field = $this->fieldFactory->getField($position);
                // Check if this is singular or a list
                if (strpos($part, ',') !== false) {
                    foreach (array_map('trim', explode(',', $part)) as $listPart) {
                        if ($field->isSatisfiedBy($nextRun, $listPart)) {
                            $satisfied = true;
                            break;
                        }
                    }
                } else {
                    $satisfied = $field->isSatisfiedBy($nextRun, $part);
                }

                // If the field is not satisfied, then start over
                if (!$satisfied) {
                    $field->increment($nextRun, $invert);
                    continue 2;
                }
            }

            // Skip this match if needed
            if ((!$allowCurrentDate && $nextRun == $currentDate) || --$nth > -1) {
                $this->fieldFactory->getField(0)->increment($nextRun, $invert);
                continue;
            }

            return $nextRun;
        }

        // @codeCoverageIgnoreStart
        throw new RuntimeException('Impossible CRON expression');
        // @codeCoverageIgnoreEnd
    }

}
