<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 18/05/2017
 * Time: 12:00 PM
 */
class Notification extends DataObject {
    private static $db = [
        'Title'         => 'Varchar(245)',
        'Details'       => 'Text',
        'Status'        => 'Enum(array("1","0"))'
    ];

    private static $has_one = [
        'Member'        => 'Member'
    ];

}