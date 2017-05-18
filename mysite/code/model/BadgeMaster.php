<?php

class BadgeMaster extends DataObject {
    private static $db = [
        'Label'                     => 'Varchar(145)', 
        'AchieveComment'            => 'Varchar(512)',
        'AchieveValue'              => 'Int', 
        'NumberOfVideosUpload'      => 'Int', 
        'NumberOfVideosWatch'       => 'Int', 
        'NumberOfVideosLike'        => 'Int', 
        'NumberOfVideosShare'       => 'Int', 
        'NumberOfVideosApproved'    => 'Int', 
        'NumberOfRareVideos'        => 'Int', 
        'NumberOfAthleteVideos'     => 'Int'
    ];

    private static $has_one = [
        'BigImage'          => 'Image', 
        'SmallIcon'         => 'Image', 
        'RareLevel'         => 'RaretyLevel', 
        'AthleteLevel'      => 'AthleteLevel'
    ];

}

?>