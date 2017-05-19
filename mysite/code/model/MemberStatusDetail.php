<?php

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 19/05/2017
 * Time: 10:50 AM
 */
class MemberStatusDetail extends DataObject {
    private static $db = [
        'Status'            => 'Enum(array("Waiting","InProcess","Approved","Rejected"))',
        'ChangeDate'        => 'Date',
        'Comments'          => 'Text',
    ];

    private static $has_one = [
        'Owner'         => 'Member',
        'ChangeBy'      => 'Member'
    ];
}