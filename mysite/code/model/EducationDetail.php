<?php

class EducationDetail extends DataObject {
    private static $db = [
        'DegreeName'        => 'Varchar(255)',
        'CollegeName'       => 'Varchar(255)', 
        'PassoutYear'       => 'Int', 
        'PassClass'         => 'Varchar(45)', 
        'Percentage'        => 'Decimal(11,2)'
    ];

    private static $has_one = [
        'SportManager'      => 'SportManager', 
        'SportAttorney'     => 'SportAttorney', 
        'Trainer'           => 'Trainer', 
        'Athlete'           => 'Athlete'        
    ];
}

?>