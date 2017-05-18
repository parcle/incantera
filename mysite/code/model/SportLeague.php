<?php

class SportLeague extends DataObject {
    private static $db = array(
        'Name'          => 'Varchar(145)', 
        'Summary'       => 'Varchar(512)', 
        'Description'   => 'Text', 
        'WebsiteURL'    => 'Varchar(255)'
    );

    private static $has_one = array(
        'BigImage'          => 'Image', 
        'SmallIcon'         => 'Image', 
        'Country'           => 'Country', 
        'Sport'             => 'Sport',
        'Logo'              => 'Image'
    );

    private static $has_many = [
        'Gallery'       => 'Image'
    ];
}

?>