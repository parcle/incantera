<?php

class SportTournamentVersion extends DataObject {
    private static $db = array(
        'Title'         => 'Varchar(255)', 
        'Year'          => 'Int'
    );

    private static $has_one = array(
        'SportTournament'   => 'SportTournament', 
        'WinningTeam'       => 'Team', 
        'RunnerUpTeam'      => 'Team'
    );

    private static $has_many = [
        'Gallery'           => 'Image'
    ];}

?>