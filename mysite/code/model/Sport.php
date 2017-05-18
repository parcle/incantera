<?php

class Sport extends DataObject {
    private static $db = [
        'Name'          => 'Varchar(145)', 
        'GameType'      => 'Enum(array("Indoor","Outdoor"))', 
        'SeasonSpecial' => 'Enum(array("Yes","No"))', 
        'Description'   => 'Text'
    ];

    private static $has_one = [
        'Image'         => 'Image'
    ];

    private static $has_many = [
        'Gallery'         => 'Image'
    ];
}

?>