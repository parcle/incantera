<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 18/05/2017
 * Time: 11:40 AM
 */
class PointWallet extends DataObject {
    private static $db = [
        'Points'            => 'Int',
        'EntryType'         => 'Varchar(512)'
    ];

    private static $has_one = [
        'Owner'             => 'Member',
        'Video'             => 'Video',
        'Athlete'           => 'Athlete'
    ];

}