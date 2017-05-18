<?php

class VideoStatusDetail extends DataObject {
    private static $db = [
        'Status'            => 'Enum(array("Wait","Approved","Rejected","PartiallyApproved"))', 
        'ChangeDate'        => 'Date', 
        'Comments'          => 'Text', 
    ];

    private static $has_one = [
        'Video'         => 'Video', 
        'ChangeBy'      => 'Member'
    ];
}

?>