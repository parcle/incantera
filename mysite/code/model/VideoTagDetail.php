<?php

class VideoTagDetail extends DataObject {
    private static $db = [
        'Tag'               => 'Varchar(145)' 
    ];

    private static $has_one = [
        'Video'         => 'Video'
    ];
}

?>