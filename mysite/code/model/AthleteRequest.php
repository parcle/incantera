<?php

class AthleteRequest extends DataObject {
    private static $db = [
        'Status'            => 'Enum(array("Waiting","Approved","Rejected"))', 
        'BlockStatus'       => 'Enum(array("No", "Yes"))', 
        'AbuseStatus'       => 'Enum(array("No", "Yes"))', 
        'RequestMessage'    => 'Varchar(512)', 
        'ResponseMessage'   => 'Varchar(512)'
    ];

    private static $has_one = [
        'Athlete'           => 'Athlete',
        'SportManager'      => 'SportManager',
        'SportAttorney'     => 'SportAttorney', 
        'Trainer'           => 'Trainer'
    ];
}
?>