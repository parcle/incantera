<?php

class AthleteLevel extends DataObject {
    private static $db = [
        'Label'             => 'Varchar(145)', 
        'UploadValue'       => 'Int', 
        'WatchingValue'     => 'Int', 
        'LikeValue'         => 'Int',
        'ShareValue'        => 'Int', 
        'TagValue'          => 'Int'
    ];

}

?>