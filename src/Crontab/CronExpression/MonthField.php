<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:05
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;

/**
 * Month field.  Allows: * , / -
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:50
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\MonthField
 */
class MonthField extends AbstractField
{

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool
    {
        // Convert text month values to integers
        $value = strtr($value, [
            'JAN' => 1,
            'FEB' => 2,
            'MAR' => 3,
            'APR' => 4,
            'MAY' => 5,
            'JUN' => 6,
            'JUL' => 7,
            'AUG' => 8,
            'SEP' => 9,
            'OCT' => 10,
            'NOV' => 11,
            'DEC' => 12,
        ]);

        return $this->isSatisfied($date->format('m'), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date, bool $invert = false): FieldInterface
    {
        $year = $date->format('Y');
        if ($invert) {
            $month = $date->format('m') - 1;
            if ($month < 1) {
                $month = 12;
                $year--;
            }
            $date->setDate($year, $month, 1);
            $date->setDate($year, $month, DayOfMonthField::getLastDayOfMonth($date));
            $date->setTime(23, 59, 0);
        } else {
            $month = $date->format('m') + 1;
            if ($month > 12) {
                $month = 1;
                $year++;
            }
            $date->setDate($year, $month, 1);
            $date->setTime(0, 0, 0);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $value): bool
    {
        return (bool)preg_match('/[\*,\/\-0-9A-Z]+/', $value);
    }

}