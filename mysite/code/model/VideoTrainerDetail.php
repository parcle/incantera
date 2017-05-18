<?php

class VideoTrainerDetail extends DataObject {
    private static $has_one = [
        'Video'         => 'Video', 
        'Trainer'       => 'Trainer'
    ];
}

?>