<?php

class VideoAttorneyDetail extends DataObject {
    private static $has_one = [
        'Video'         => 'Video', 
        'SportAttorney'  => 'SportAttorney'
    ];
}

?>