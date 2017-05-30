<?php

class AthleteRequestStatusDetail extends DataObject {
    private static $db = [
        'OldStatus'            => 'Enum(array("Waiting","Approved","Rejected", "Block", "ReportAbuse"))', 
        'NewStatus'            => 'Enum(array("Waiting","Approved","Rejected", "Block", "ReportAbuse"))'
    ];

    private static $has_one = [
        'AthleteRequest'        => 'AthleteRequest',
    ];
}
?>