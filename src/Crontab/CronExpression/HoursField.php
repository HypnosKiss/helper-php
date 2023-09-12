<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:05
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;

/**
 * Hours field.  Allows: * , / -
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:48
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\HoursField
 */
class HoursField extends AbstractField
{

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool
    {
        return $this->isSatisfied($date->format('H'), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date, bool $invert = false): FieldInterface
    {
        if ($invert) {
            $date->modify('-1 hour');
            $date->setTime($date->format('H'), 59, 0);
        } else {
            $date->modify('+1 hour');
            $date->setTime($date->format('H'), 0, 0);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $value): bool
    {
        return (bool)preg_match('/[\*,\/\-0-9]+/', $value);
    }

}