<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 18/05/2017
 * Time: 11:44 AM
 */
class Merchandize extends DataObject {
    private static $db = [
        'Title'             => 'Varchar(155)',
        'ShortDescription'  => 'Varchar(512)',
        'Description'       => 'Text',
        'ValueInPoints'     => 'Int'
    ];

}