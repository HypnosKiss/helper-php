<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:04
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

/**
 * Abstract CRON expression field
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:33
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\AbstractField
 */
abstract class AbstractField implements FieldInterface
{

    /**
     * Check to see if a field is satisfied by a value
     * @param string $dateValue Date value to check
     * @param string $value     Value to test
     * @return bool
     */
    public function isSatisfied(string $dateValue, string $value): bool
    {
        if ($this->isIncrementsOfRanges($value)) {
            return $this->isInIncrementsOfRanges($dateValue, $value);
        }

        if ($this->isRange($value)) {
            return $this->isInRange($dateValue, $value);
        }

        return $value === '*' || $dateValue === $value;
    }

    /**
     * Check if a value is a range
     * @param string $value Value to test
     * @return bool
     */
    public function isRange(string $value): bool
    {
        return strpos($value, '-') !== false;
    }

    /**
     * Check if a value is an increments of ranges
     * @param string $value Value to test
     * @return bool
     */
    public function isIncrementsOfRanges(string $value): bool
    {
        return strpos($value, '/') !== false;
    }

    /**
     * Test if a value is within a range
     * @param string $dateValue Set date value
     * @param string $value     Value to test
     * @return bool
     */
    public function isInRange(string $dateValue, string $value): bool
    {
        $parts = array_map('trim', explode('-', $value, 2));

        return $dateValue >= $parts[0] && $dateValue <= $parts[1];
    }

    /**
     * Test if a value is within an increments of ranges (offset[-to]/step size)
     * @param string $dateValue Set date value
     * @param string $value     Value to test
     * @return bool
     */
    public function isInIncrementsOfRanges(string $dateValue, string $value): bool
    {
        $parts    = array_map('trim', explode('/', $value, 2));
        $stepSize = $parts[1] ?? 0;
        if ($parts[0] === '*' || $parts[0] == 0) {
            return (int)$dateValue % $stepSize == 0;
        }

        $range  = explode('-', $parts[0], 2);
        $offset = $range[0];
        $to     = $range[1] ?? $dateValue;
        // Ensure that the date value is within the range
        if ($dateValue < $offset || $dateValue > $to) {
            return false;
        }

        for ($i = $offset; $i <= $to; $i += $stepSize) {
            if ($i == $dateValue) {
                return true;
            }
        }

        return false;
    }

}