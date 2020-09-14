<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     modifier.day_of_week.php
 * Type:     modifier
 * Name:     day_of_week
 * Purpose:  指定のepochTimeの曜日を返す
 * -------------------------------------------------------------
 */
function smarty_modifier_day_of_week($epochTime)
{
    $dowList = [
        '日',
        '月',
        '火',
        '水',
        '木',
        '金',
        '土'
    ];

    return $dowList[date('w', $epochTime)];
}
