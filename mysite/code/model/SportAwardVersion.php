<?php

class SportAwardVersion extends DataObject {
    private static $db = array(
        'AnnounceDate'  => 'Date', 
        'Summary'       => 'Varchar(512)', 
        'Description'   => 'Text', 
        'Year'          => 'Int'
    );

    private static $has_one = array(
        'SportAward'        => 'SportAward', 
        'WinningTeam'       => 'Team', 
        'WinningAthlete'    => 'Athlete',
        'Logo'              => 'Image'
    );

    private static $has_many = [
        'Gallery'           => 'Image'
    ];
}

?>