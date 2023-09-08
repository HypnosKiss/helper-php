<?php
/**
 * Created by PhpStorm.
 * User: sweeper
 * Time: 2022/10/12 16:50
 */

namespace Sweeper\HelperPhp\Tool;

use \DateTime;
use Exception;

/**
 * 工作日计算
 * Created by PhpStorm.
 * User: sweeper
 * Time: 2023/8/27 22:58
 * @Path \Sweeper\HelperPhp\Tool\BusinessDaysCalculator
 * @example
 * $holidays           = ["2017-01-27", "2017-01-28"];//假期
 * $specialBusinessDay = ["2017-01-22"];//指定工作日
 * $calculator         = new BusinessDaysCalculator(new \DateTime(), $holidays, [BusinessDaysCalculator::SATURDAY, BusinessDaysCalculator::SUNDAY], $specialBusinessDay);
 * echo $calculator->addBusinessDays(2)->getDate();// 2个工作日后的时间
 */
class BusinessDaysCalculator
{

    public const MONDAY    = 1;

    public const TUESDAY   = 2;

    public const WEDNESDAY = 3;

    public const THURSDAY  = 4;

    public const FRIDAY    = 5;

    public const SATURDAY  = 6;

    public const SUNDAY    = 7;

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var array
     */
    private $holidays;

    /**
     * @var DateTime[]
     */
    private $nonBusinessDays;

    /**
     * @var DateTime[]
     */
    private $specialBusinessDay;

    /**
     * 是工作日
     * User: sweeper
     * Time: 2022/10/12 17:06
     * @param \DateTime $date
     * @return bool
     */
    private function isBusinessDay(DateTime $date): bool
    {
        //判断当前日期是否是因法定节假日调休而上班的周末，这种情况也算工作日 ['Y-m-d']
        if (in_array($date->format('Y-m-d'), $this->specialBusinessDay, true)) {
            return true;
        }

        //当前日期是周末 [1-7]
        if (in_array((int)$date->format('N'), $this->nonBusinessDays, true)) {
            return false;
        }
        //当前日期是法定节假日
        foreach ($this->holidays as $day) {
            if ($date->format('Y-m-d') === $day->format('Y-m-d')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param DateTime $startDate          Date to start calculations from 开始计算的日期
     * @param array    $holidays           Array of holidays, holidays are no conisdered business days. 一连串的假日，假日都不是连续的工作日。['Y-m-d'] 格式，如：["2023-05-01", "2023-05-02", "2023-05-03"]
     * @param array    $nonBusinessDays    Array of days of the week which are not business days. 一周中非工作日的天数数组。[1-7] 格式，如：[6, 7]
     * @param array    $specialBusinessDay Array is the special work day. 因法定节假日调休而上班的周末，这种情况也算工作日.因为这种情况少，可以通过手动配置。['Y-m-d'] 格式，如：["2023-05-06"]
     * @throws Exception
     */
    public function __construct(DateTime $startDate, array $holidays, array $nonBusinessDays, array $specialBusinessDay)
    {
        $this->date     = $startDate;
        $this->holidays = [];
        foreach ($holidays as $holiday) {
            $this->holidays[] = new DateTime($holiday);
        }
        $this->nonBusinessDays    = $nonBusinessDays;
        $this->specialBusinessDay = $specialBusinessDay;
    }

    /**
     * User: sweeper
     * Time: 2022/10/12 17:06
     * @param $howManyDays
     * @return $this
     */
    public function addBusinessDays($howManyDays): self
    {
        $i = 0;
        while ($i < $howManyDays) {
            $this->date->modify("+1 day");
            if ($this->isBusinessDay($this->date)) {
                $i++;
            }
        }

        return $this;
    }

    /**
     * 获取指定格式日期
     * User: Sweeper
     * Time: 2023/9/6 11:38
     * @param $format
     * @return string
     */
    public function getDate($format = 'Y-m-d H:i:s'): string
    {
        return $this->date->format($format);
    }

}
