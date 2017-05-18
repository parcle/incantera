<?php

class SportClub extends DataObject {
    private static $db = array(
        'Name'          => 'Varchar(145)', 
        'Summary'       => 'Varchar(512)', 
        'Description'   => 'Text', 
        'WebsiteURL'    => 'Varchar(255)'
    );

    private static $has_one = array(
        'BigImage'          => 'Image', 
        'SmallIcon'         => 'Image', 
        'Country'           => 'Country'
    );

    private static $has_many = [
        'Sports'        => 'ClubSportDetail', 
        'Gallery'       => 'Image'
    ];
}

?>