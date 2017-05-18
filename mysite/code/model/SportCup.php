<?php

class SportCup extends DataObject {
    private static $db = array(
        'Name'              => 'Varchar(145)', 
        'ShortName'         => 'Varchar(15)', 
        'Duration'          => 'Int',
        'ShortDescription'  => 'Varchar(512)', 
        'Description'       => 'Text', 
        'WebsiteURL'        => 'Varchar(255)'
    );

    private static $has_one = array(
        'BigImage'          => 'Image', 
        'SmallIcon'         => 'Image', 
        'Sport'             => 'Sport'
    );

    private static $has_many = [
        'Team'          => 'Team', 
        'Gallery'       => 'Image'
    ];

}

?>