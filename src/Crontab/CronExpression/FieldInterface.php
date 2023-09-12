<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:04
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;

/**
 * CRON field interface
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:33
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\FieldInterface
 */
interface FieldInterface
{

    /**
     * Check if the respective value of a DateTime field satisfies a CRON exp
     * @param DateTime $date  DateTime object to check
     * @param string   $value CRON expression to test against
     * @return bool Returns TRUE if satisfied, FALSE otherwise
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool;

    /**
     * When a CRON expression is not satisfied, this method is used to increment
     * or decrement a DateTime object by the unit of the cron field
     * @param DateTime $date   DateTime object to change
     * @param bool     $invert (optional) Set to TRUE to decrement
     * @return FieldInterface
     */
    public function increment(DateTime $date, bool $invert = false): FieldInterface;

    /**
     * Validates a CRON expression for a given field
     * @param string $value CRON expression value to validate
     * @return bool Returns TRUE if valid, FALSE otherwise
     */
    public function validate(string $value): bool;

}