<?php

class SportAttributeGroup extends DataObject {
    private static $db = array(
        'Name'          => 'Varchar(145)', 
        'Description'   => 'Text', 
        'SequenceNum'   => 'Int'
    );

    private static $has_one = array(
        'Sport'         => 'Sport'
    );
}

?>