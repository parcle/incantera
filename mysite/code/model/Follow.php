<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 19/05/2017
 * Time: 12:04 AM
 */
class Follow extends DataObject {
    private static $has_one = [
        'Video'             => 'Video',
        'Athlete'           => 'Athlete',
        'Member'            => 'Member'
    ];
}