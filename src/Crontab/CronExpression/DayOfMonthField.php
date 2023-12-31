<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:04
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;

/**
 * Day of month field.  Allows: * , / - ? L W
 * 'L' stands for 'last' and specifies the last day of the month.
 * The 'W' character is used to specify the weekday (Monday-Friday) nearest the
 * given day. As an example, if you were to specify '15W' as the value for the
 * day-of-month field, the meaning is: 'the nearest weekday to the 15th of the
 * month'. So if the 15th is a Saturday, the trigger will fire on Friday the
 * 14th. If the 15th is a Sunday, the trigger will fire on Monday the 16th. If
 * the 15th is a Tuesday, then it will fire on Tuesday the 15th. However if you
 * specify '1W' as the value for day-of-month, and the 1st is a Saturday, the
 * trigger will fire on Monday the 3rd, as it will not 'jump' over the boundary
 * of a month's days. The 'W' character can only be specified when the
 * day-of-month is a single day, not a range or list of days.
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:37
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\DayOfMonthField
 */
class DayOfMonthField extends AbstractField
{

    /**
     * Get the last day of the month
     * @param DateTime $date Date object to check
     * @return int returns the last day of the month
     */
    public static function getLastDayOfMonth(DateTime $date): int
    {
        $month = $date->format('n');
        if ($month == 2) {
            return (bool)$date->format('L') ? 29 : 28;
        }

        $dates = [
            1  => 31,
            3  => 31,
            4  => 30,
            5  => 31,
            6  => 30,
            7  => 31,
            8  => 31,
            9  => 30,
            10 => 31,
            11 => 30,
            12 => 31,
        ];

        return $dates[$month];
    }

    /**
     * Get the nearest day of the week for a given day in a month
     * @param int $currentYear  Current year
     * @param int $currentMonth Current month
     * @param int $targetDay    Target day of the month
     * @return DateTime Returns the nearest date
     */
    private static function getNearestWeekday(int $currentYear, int $currentMonth, int $targetDay): ?DateTime
    {
        $tday           = str_pad($targetDay, 2, '0', STR_PAD_LEFT);
        $target         = DateTime::createFromFormat('Y-m-d', "$currentYear-$currentMonth-$tday");
        $currentWeekday = (int)$target->format('N');

        if ($currentWeekday < 6) {
            return $target;
        }

        $lastDayOfMonth = self::getLastDayOfMonth($target);

        foreach ([-1, 1, -2, 2] as $i) {
            $adjusted = $targetDay + $i;
            if ($adjusted > 0 && $adjusted <= $lastDayOfMonth) {
                $target->setDate($currentYear, $currentMonth, $adjusted);
                if ($target->format('N') < 6 && $target->format('m') == $currentMonth) {
                    return $target;
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool
    {
        // ? states that the field value is to be skipped
        if ($value === '?') {
            return true;
        }

        $fieldValue = $date->format('d');

        // Check to see if this is the last day of the month
        if ($value === 'L') {
            return $fieldValue == self::getLastDayOfMonth($date);
        }

        // Check to see if this is the nearest weekday to a particular value
        if (strpos($value, 'W')) {
            // Parse the target day
            $targetDay = substr($value, 0, strpos($value, 'W'));

            // Find out if the current day is the nearest day of the week
            return $date->format('j') == self::getNearestWeekday($date->format('Y'), $date->format('m'), $targetDay)->format('j');
        }

        return $this->isSatisfied($date->format('d'), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date, bool $invert = false): FieldInterface
    {
        if ($invert) {
            $date->modify('-1 day');
            $date->setTime(23, 59, 0);
        } else {
            $date->modify('+1 day');
            $date->setTime(0, 0, 0);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $value): bool
    {
        return (bool)preg_match('/[\*,\/\-\?LW0-9A-Za-z]+/', $value);
    }

}