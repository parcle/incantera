<?php

class VideoManagerDetail extends DataObject {
    private static $has_one = [
        'Video'         => 'Video', 
        'SportManager'  => 'SportManager'
    ];
}

?>