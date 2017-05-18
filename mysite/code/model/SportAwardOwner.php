<?php

class SportAwardOwner extends DataObject {
    private static $db = array(
        'Name'          => 'Varchar(145)', 
        'Summary'       => 'Varchar(512)', 
        'Description'   => 'Text', 
        'WebsiteURL'    => 'Varchar(255)'
    );

    private static $has_one = [
        'Logo'          => 'Image'
    ];

    private static $has_many = array(
        'SportAward'    => 'SportAward',
        'Gallery'           => 'Image'
    );

}

?>