<?php

class Country extends DataObject {
    private static $db = array(
        'Name'          => 'Varchar(145)', 
        'ShortName'     => 'Varchar(15)'
    );

    private static $has_one = array(
        'BigImage'          => 'Image', 
        'SmallIcon'         => 'Image'
    );
}

?>