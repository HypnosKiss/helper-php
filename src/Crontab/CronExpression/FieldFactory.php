<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/11 13:04
 */

namespace Sweeper\HelperPhp\Crontab\CronExpression;

use Exception;

/**
 * CRON field factory implementating a flyweight factory
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/9/12 15:47
 * @Path \Sweeper\HelperPhp\Crontab\CronExpression\FieldFactory
 * @link   http://en.wikipedia.org/wiki/Cron
 */
class FieldFactory
{

    /**
     * @var array Cache of instantiated fields
     */
    private $fields = [];

    /**
     * Get an instance of a field object for a cron expression position
     * @param int $position CRON expression position value to retrieve
     * @return FieldInterface
     * @throws Exception if a position is not valide
     */
    public function getField(int $position)
    {
        if (!isset($this->fields[$position]))
            switch ($position) {
                case 0:
                    $this->fields[$position] = new MinutesField();
                    break;
                case 1:
                    $this->fields[$position] = new HoursField();
                    break;
                case 2:
                    $this->fields[$position] = new DayOfMonthField();
                    break;
                case 3:
                    $this->fields[$position] = new MonthField();
                    break;
                case 4:
                    $this->fields[$position] = new DayOfWeekField();
                    break;
                case 5:
                    $this->fields[$position] = new YearField();
                    break;
                default:
                    throw new \RuntimeException($position . ' is not a valid position');
            }

        return $this->fields[$position];
    }

}