<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:05
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use DateTime;

/**
 * Year field.  Allows: * , / -
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * Time: 2023/9/12 16:02
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\YearField
 */
class YearField extends AbstractField
{

    /**
     * {@inheritdoc}
     */
    public function isSatisfiedBy(DateTime $date, string $value): bool
    {
        return $this->isSatisfied($date->format('Y'), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function increment(DateTime $date, bool $invert = false): FieldInterface
    {
        if ($invert) {
            $date->modify('-1 year');
            $date->setDate($date->format('Y'), 12, 31);
            $date->setTime(23, 59, 0);
        } else {
            $date->modify('+1 year');
            $date->setDate($date->format('Y'), 1, 1);
            $date->setTime(0, 0, 0);
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