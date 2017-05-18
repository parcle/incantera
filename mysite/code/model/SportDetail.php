<?php

class SportDetail extends DataObject {
    private static $db = [
        'CurrentlyWorking'  => 'Enum(array("Yes","No"))',
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