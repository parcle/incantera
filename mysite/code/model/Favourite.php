<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 19/05/2017
 * Time: 12:05 AM
 */
class Favourite extends DataObject {
    private static $has_one = [
        'Video'             => 'Video',
        'Athlete'           => 'Athlete',
        'Member'            => 'Member'
    ];
}