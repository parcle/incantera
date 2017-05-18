<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 18/05/2017
 * Time: 11:47 AM
 */
class MerchandizeBadgeReq extends DataObject {
    private static $db = [
        'NumberOfBadge'     => 'Int'
    ];

    private static $has_one = [
        'Merchandize'       => 'Merchandize',
        'Badge'             => 'BadgeMaster'
    ];
}