<?php

class VideoAthleteDetail extends DataObject {
    private static $has_one = [
        'Video'         => 'Video', 
        'Athlete'       => 'Athlete'
    ];
}

?>