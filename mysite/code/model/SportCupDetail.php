<?php

class SportCupDetail extends DataObject {
    private static $db = [
        'Status'            => 'Enum(array("Winner","1stRunnerUp","2ndRunnerUp","QuarterFinal","Loss"))',
        'Year'              => 'Int'
    ];

    private static $has_one = [
        'SportCup'          => 'SportCupVersion', 
        'SportManager'      => 'SportManager', 
        'SportAttorney'     => 'SportAttorney', 
        'Trainer'           => 'Trainer', 
        'Athlete'           => 'Athlete'        
    ];
}

?>