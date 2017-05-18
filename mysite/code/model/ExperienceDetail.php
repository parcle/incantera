<?php

class ExperienceDetail extends DataObject {
    private static $db = [
        'WorkingTitle'      => 'Varchar(255)',
        'OrganizationName'  => 'Varchar(255)', 
        'StartDate'         => 'Date', 
        'EndDate'           => 'Date', 
        'CurrentJob'        => 'Enum(array("Yes","No"))'
    ];

    private static $has_one = [
        'SportManager'      => 'SportManager', 
        'SportAttorney'     => 'SportAttorney', 
        'Trainer'           => 'Trainer'
    ];
}

?>