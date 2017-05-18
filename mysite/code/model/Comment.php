<?php

class Comment extends DataObject {
    private static $db = [
        'Comment'               => 'Varchar(512)', 
        'CommentDate'           => 'Date'
    ];    
    private static $has_one = [
        'ParentComment'     => 'Comment', 
        'Video'             => 'Video',
        'Athlete'           => 'Athlete', 
        'Member'            => 'Member'
    ];
}

?>