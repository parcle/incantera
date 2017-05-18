<?php

class ImportantAffair extends DataObject {
    private static $db = [
        'AffairTitle'       => 'Varchar(255)',
        'ShortDescription'  => 'Varchar(512)', 
        'Description'       => 'Text', 
        'StartDate'         => 'Date', 
        'EndDate'           => 'Date', 
        'Year'              => 'Int'
    ];

    private static $has_one = [
        'SportManager'      => 'SportManager', 
        'SportAttorney'     => 'SportAttorney'
    ];
}

?>