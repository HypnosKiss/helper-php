<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:05
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;

/**
 * Day of week field.  Allows: * / , - ? L #
 * Days of the week can be represented as a number 0-7 (0|7 = Sunday)
 * or as a three letter string: SUN, MON, TUE, WED, THU, FRI, SAT.
 * 'L' stands for 'last'. It allows you to specify constructs such as
 * 'the last Friday' of a given month.
 * '#' is allowed for the day-of-week field, and must be followed by a
 * number between one and five. It allows you to specify constructs such as
 * 'the second Friday' of a given month.
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:42
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\DayOfWeekField
 */
class DayOfWeekField extends AbstractField
{

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool
    {
        if ($value === '?') {
            return true;
        }

        // Convert text day of the week values to integers
        $value = strtr($value, [
            'SUN' => 0,
            'MON' => 1,
            'TUE' => 2,
            'WED' => 3,
            'THU' => 4,
            'FRI' => 5,
            'SAT' => 6,
        ]);

        $currentYear    = $date->format('Y');
        $currentMonth   = $date->format('m');
        $lastDayOfMonth = DayOfMonthField::getLastDayOfMonth($date);

        // Find out if this is the last specific weekday of the month
        if (strpos($value, 'L')) {
            $weekday = str_replace('7', '0', substr($value, 0, strpos($value, 'L')));
            $tdate   = clone $date;
            $tdate->setDate($currentYear, $currentMonth, $lastDayOfMonth);
            while ($tdate->format('w') != $weekday) {
                $tdate->setDate($currentYear, $currentMonth, --$lastDayOfMonth);
            }

            return $date->format('j') == $lastDayOfMonth;
        }

        // Handle # hash tokens
        if (strpos($value, '#')) {
            [$weekday, $nth] = explode('#', $value);
            // Validate the hash fields
            if ($weekday < 1 || $weekday > 5) {
                throw new \RuntimeException("Weekday must be a value between 1 and 5. {$weekday} given");
            }
            if ($nth > 5) {
                throw new \RuntimeException('There are never more than 5 of a given weekday in a month');
            }
            // The current weekday must match the targeted weekday to proceed
            if ($date->format('N') != $weekday) {
                return false;
            }

            $tdate = clone $date;
            $tdate->setDate($currentYear, $currentMonth, 1);
            $dayCount   = 0;
            $currentDay = 1;
            while ($currentDay < $lastDayOfMonth + 1) {
                if ($tdate->format('N') == $weekday) {
                    if (++$dayCount >= $nth) {
                        break;
                    }
                }
                $tdate->setDate($currentYear, $currentMonth, ++$currentDay);
            }

            return $date->format('j') == $currentDay;
        }

        // Handle day of the week values
        if (strpos($value, '-')) {
            $parts = explode('-', $value);
            if ($parts[0] == '7') {
                $parts[0] = '0';
            } else {
                if ($parts[1] == '0') {
                    $parts[1] = '7';
                }
            }
            $value = implode('-', $parts);
        }

        // Test to see which Sunday to use -- 0 == 7 == Sunday
        $format     = in_array(7, str_split($value), false) ? 'N' : 'w';
        $fieldValue = $date->format($format);

        return $this->isSatisfied($fieldValue, $value);
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