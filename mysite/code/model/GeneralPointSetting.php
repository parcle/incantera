<?php

class GeneralPointSetting extends DataObject {
    private static $db = [
        'UploadVideoValue'      => 'Int', 
        'ApprovalVideoValue'    => 'Int', 
        'WatchingVideoValue'    => 'Int', 
        'LikeVideoValue'        => 'Int',
        'ShareVideoValue'       => 'Int', 
        'AtheleteLikeValue'     => 'Int', 
        'AthleteShareValue'     => 'Int'
    ];

}

?>