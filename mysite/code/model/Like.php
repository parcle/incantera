<?php

class Like extends DataObject {
    private static $has_one = [
        'Video'             => 'Video',
        'Athlete'           => 'Athlete', 
        'Member'            => 'Member'
    ];
}

?>