<?php

class Athlete extends DataObject {
    private static $db = [
        'Title'             => 'Varchar(145)', 
        'BusineesName'      => 'Varchar(145)',
        'Gender'            => 'Enum(array("Male", "Female", "Unknown"))', 
        'BirthDate'         => 'Date', 
        'MarritalStatus'    => 'Enum(array("Yes", "No"))', 
        'MarriageDate'      => 'Date', 
        'WebsiteURL'        => 'Varchar(145)', 
        'Address'           => 'Varchar(255)', 
        'City'              => 'Varchar(145)', 
        'Zipcode'           => 'Varchar(15)', 
        'OfficialEmail'     => 'Varchar(145)', 
        'OfficialContactNo' => 'Varchar(45)', 
        'OfficialMobile'    => 'Varchar(45)', 
        'JobSummary'        => 'Varchar(512)', 
        'JobDescription'    => 'Text', 
        'Position'          => 'Varchar(145)'
    ];

    private static $has_one = [
        'Sport'             => 'Sport',
        'AthleteLevel'      => 'AthleteLevel',
        'Country'           => 'Country', 
        'Member'            => 'Member',
        'Trainer'           => 'Trainer'
    ];

    private static $has_many = [
        'Gallery'           => 'Image'
    ];
}

?>