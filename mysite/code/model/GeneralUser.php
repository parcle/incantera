<?php

class GeneralUser extends DataObject {
    private static $db = [
        'Gender'            => 'Enum(array("Male", "Female", "Unknown"))', 
        'BirthDate'         => 'Date', 
        'MarritalStatus'    => 'Enum(array("Yes", "No"))', 
        'MarriageDate'      => 'Date', 
        'Address'           => 'Varchar(255)', 
        'City'              => 'Varchar(145)', 
        'Zipcode'           => 'Varchar(15)', 
        'Description'       => 'Text'
    ];

    private static $has_one = [
        'Country'           => 'Country', 
        'Member'            => 'Member'
    ];
}

?>