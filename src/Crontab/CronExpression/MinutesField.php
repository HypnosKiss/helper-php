<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:05
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;

/**
 * Minutes field.  Allows: * , / -
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:49
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\MinutesField
 */
class MinutesField extends AbstractField
{

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool
    {
        return $this->isSatisfied($date->format('i'), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date, bool $invert = false): FieldInterface
    {
        if ($invert) {
            $date->modify('-1 minute');
        } else {
            $date->modify('+1 minute');
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