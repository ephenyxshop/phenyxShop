<?php

/**
 * Class DateRangeCore
 *
 * @since 1.9.1.0
 */
class DateRangeCore extends ObjectModel {

    // @codingStandardsIgnoreStart
    /** @var string $time_start */
    public $time_start;
    /** @var string $time_end */
    public $time_end;
    // @codingStandardsIgnoreEnd
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'date_range',
        'primary' => 'id_date_range',
        'fields'  => [
            'time_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
            'time_end'   => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        ],
    ];

    /**
     * @return mixed
     *
     * @throws PhenyxShopDatabaseException
     * @throws PhenyxShopException
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    public static function getCurrentRange() {

        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('`id_date_range`, `time_end`')
                ->from('date_range')
                ->where('`time_end` = (SELECT MAX(`time_end`) FROM `' . _DB_PREFIX_ . 'date_range`)')
        );

        if (!$result['id_date_range'] || strtotime($result['time_end']) < strtotime(date('Y-m-d H:i:s'))) {
            // The default range is set to 1 day less 1 second (in seconds)
            $rangeSize = 86399;
            $dateRange = new static();
            $dateRange->time_start = date('Y-m-d');
            $dateRange->time_end = strftime('%Y-%m-%d %H:%M:%S', strtotime($dateRange->time_start) + $rangeSize);
            $dateRange->add();

            return $dateRange->id;
        }

        return $result['id_date_range'];
    }

}
