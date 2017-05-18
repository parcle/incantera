<?php

class SportAward extends DataObject {
    private static $db = array(
        'Name'          => 'Varchar(145)', 
        'Summary'       => 'Varchar(512)', 
        'Description'   => 'Text', 
        'AwardType'     => 'Enum(array("Team","Person"))'
    );

    private static $has_one = array(
        'Owner'         => 'SportAwardOwner', 
        'Sport'         => 'Sport',
        'Logo'          => 'Image'
    );

    private static $has_many = [
        'Gallery'           => 'Image'
    ];
}

?>