<?php

class SportCupVersion extends DataObject {
    private static $db = array(
        'Title'         => 'Varchar(255)', 
        'Year'          => 'Int'
    );

    private static $has_one = array(
        'SportCup'          => 'SportCup', 
        'WinningTeam'       => 'Team', 
        'RunnerUpTeam'      => 'Team'
    );
}

?>