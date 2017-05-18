<?php

class FamilyDetail extends DataObject {
    private static $db = [
        'PersonName'        => 'Varchar(255)',
        'RelationShip'      => 'Varchar(255)', 
        'StartDate'         => 'Date', 
        'EndDate'           => 'Date'
    ];

    private static $has_one = [
        'SportManager'      => 'SportManager', 
        'SportAttorney'     => 'SportAttorney', 
        'Trainer'           => 'Trainer', 
        'Athlete'           => 'Athlete'        
    ];
}

?>