<?php

class GeneralUser extends DataObject {
    private static $db = [
        'Gender'            => 'Enum(array("None", "Male", "Female", "Unknown"))',
        'BirthDate'         => 'Date', 
        'MarritalStatus'    => 'Enum(array("None", "Yes", "No"))',
        'MarriageDate'      => 'Date', 
        'Address'           => 'Varchar(255)', 
        'City'              => 'Varchar(145)', 
        'Zipcode'           => 'Varchar(15)', 
        'Description'       => 'Text',
        'Status'            => 'Enum(array("Waiting","InProcess","Approved","Rejected"))'
    ];

    private static $has_one = [
        'Country'           => 'Country', 
        'Member'            => 'Member'
    ];
}

?>
