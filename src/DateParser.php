<?php

namespace HughCube\Crontab\Parse;

class DateParser
{

    # Example of job definition:
    # .---------------- minute (0 - 59)
    # |  .------------- hour (0 - 23)
    # |  |  .---------- day of month (1 - 31)
    # |  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ...
    # |  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat
    # |  |  |  |  |
    # *  *  *  *  * user-name  command to be executed
    private $_dateFormula;

    /**
     * @var integer 时间戳
     */
    private $_timestamp;

    /**
     * DateParser constructor.
     * @param $formula
     */
    public function __construct($dateFormula = '* * * * *')
    {
        $this->_dateFormula = trim($dateFormula, ' ');
    }

    /**
     * 解析单个类型的时间
     *
     * 指定范围内的一个数。要在 5 月运行一个命令，在 month 字段指定 5。
     * 两个被破折号分开的数目表明了包含的范围。要从星期二到星期五运行 cron 作业，把 2-5 放到 weekday 字段中。
     * 由逗号隔开的数字列表。要在一月第一天和最后一天的运行命令，在 day_of_month 字段中可指定 1,31。
     * 由两个被破折号分开的数字组成的组合表明了包含的范围，可与由逗号格开的数字列表一起使用。 要在一月的第 10 天到第 16 天和最后一天首次运行命令，应该在 day_of_month 字段中指定 1,10-16,31。以上两点也可用在组合中。
     * *（星号），表示所有的允许值。要每个小时运行一个作业，在小时字段指定一个星号。
     *
     * @param string $string
     * @param integer $min
     * @param integer $max
     * @return integer[]
     */
    protected function parseOne($string, $min, $max)
    {
        $string = trim($string, ' ');

        $results = [];

        /**
         * 全量执行
         */
        if ('*' === $string){
            $results = static::rangeArray($min, $max);
            goto Results;
        }

        /**
         * 指定时间
         */
        if (static::isDigit($string)){
            $results = [$string];
            goto Results;
        }

        /**
         * 解析 *\/1
         */
        $array = explode('/', $string);
        if (2 === count($array) && '*' === $array[0] && static::isDigit($array[1])){
            $results = static::rangeArray($min, $max, $array[1]);
            goto Results;
        }

        /**
         * 解析 1-10/1   |    1-10
         */
        $array = explode('/', $string);
        $array[1] = isset($array[1]) ? $array[1] : '1';
        if (2 === count($array) && static::isDigit($array[1])){
            /** 分割第一个标识范围的 */
            $array[0] = explode('-', $array[0]);
            if (2 === count($array[0]) && static::isDigit($array[0][0]) && static::isDigit($array[0][1])){
                $results = static::rangeArray($array[0][0], $array[0][1], $array[1]);
                goto Results;
            }
        }

        /**
         * 解析 , 号
         * 1 , *\/2 , 1-10\/2
         */
        $array = explode(',', $string);
        if (!empty($array)){
            foreach($array as $value){
                $items = $this->parseOne($value, $min, $max);
                foreach($items as $item){
                    $results[] = $item;
                }
            }
            goto Results;
        }

        Results:


        $results = array_map('intval', array_keys(array_flip($results)));
        asort($results);

        return $results;
    }

    /**
     * 判断当前时间是否可以执行
     *
     * @return bool
     */
    public function isRuntime()
    {
        $array = explode(' ', $this->_dateFormula);
        $array[0] = isset($array[0]) ? $array[0] : '*';
        $array[1] = isset($array[1]) ? $array[1] : '*';
        $array[2] = isset($array[2]) ? $array[2] : '*';
        $array[3] = isset($array[3]) ? $array[3] : '*';
        $array[4] = isset($array[4]) ? $array[4] : '*';

        $timestamp = $this->getTimestamp();

        /**
         * minute 0 到 59
         */
        if (!in_array(((int)date('i', $timestamp)), $this->parseOne($array[0], 0, 59))){
            return false;
        }

        /**
         * hour    0 到 23
         */
        if (!in_array(((int)date('G', $timestamp)), $this->parseOne($array[1], 0, 23))){
            return false;
        }

        /**
         * day_of_month    1 到 31
         */
        if (!in_array(((int)date('j', $timestamp)), $this->parseOne($array[2], 1, 31))){
            return false;
        }

        /**
         * month    1 到 12
         */
        if (!in_array(((int)date('n', $timestamp)), $this->parseOne($array[3], 1, 13))){
            return false;
        }

        /**
         * weekday    0 到 6（星期日到星期六）
         */
        if (!in_array(((int)date('w', $timestamp)), $this->parseOne($array[4], 0, 6))){
            return false;
        }

        return true;
    }

    /**
     * 根据时间返回新的对象
     *
     * @param null|integer $time
     * @return static
     */
    public function withTimestamp($time = null)
    {
        $instance = clone $this;
        $instance->_timestamp = ((null === $time) ? time() : $time);

        return $instance;
    }

    /**
     * 返回当前时间
     *
     * @return int
     */
    public function getTimestamp()
    {
        return null === $this->_timestamp ? time() : $this->_timestamp;
    }

    /**
     * Create an array containing a range of elements
     *
     * @param integer $start
     * @param integer $end
     * @param int $step
     * @return array
     */
    protected function rangeArray($start, $end, $step = 1)
    {
        if (max($start, $end) < $step){
            return [];
        }

        return range($start, $end, $step);
    }

    /**
     * 是否是一个数字
     *
     * @param string $number
     * @return bool
     */
    protected static function isDigit($number)
    {
        return is_numeric($number) && ctype_digit(((string)$number));
    }

    /**
     * 实例化对象
     *
     * @param string $dateFormula
     * @return static
     */
    public static function instance($dateFormula)
    {
        return new static($dateFormula);
    }
}
