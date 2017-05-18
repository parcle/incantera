<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 18/05/2017
 * Time: 11:43 AM
 */
class BadgeWallet extends DataObject {
    private static $db = [
        'GetDate'           => 'Date',
        'SpendStatus'       => 'Enum(array("InWallet","Spend"))'
    ];

    private static $has_one = [
        'Owner'             => 'Member',
        'Badge'             => 'BadgeMaster'
    ];
}