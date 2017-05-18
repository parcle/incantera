<?php

class Video extends DataObject {
    private static $db = [
        'Title'             => 'Varchar(145)', 
        'ShortDescription'  => 'Varchar(512)',
        'Description'       => 'Text',
        'Status'            => 'Enum(array("Wait","Approved","Rejected","PartiallyApproved"))',
        'UploadDate'        => 'Date', 
        'ApprovalDate'      => 'Date', 
        'Published'         => 'Enum(array("Yes","No"))'
    ];

    private static $has_one = [
        'RareLevel'         => 'RaretyLevel',
        'UploadBy'          => 'SportManager',
        'Image'             => 'Image',
        'Video'             => 'Image'
    ];
}

?>