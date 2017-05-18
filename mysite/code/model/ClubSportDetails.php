<?php

class ClubSportDetails extends DataObject {
    private static $has_one = [
        'Club'          => 'SportClub', 
        'Sport'         => 'Sport'        
    ];
}

?>