<?php

class ImportantDealDetail extends DataObject {
    private static $db = [
        'DealTitle'         => 'Varchar(255)',
        'DealValue'         => 'Decimal(11,2)', 
        'DealFor'           => 'Enum(array("Athlete","Club"))',
        'DealSuccess'       => 'Enum(array("Yes","No"))',
        'DealStatus'         => 'Enum(array("Open","Close"))',
        'ShortDescription'  => 'Varchar(512)', 
        'StartDate'         => 'Date', 
        'EndDate'           => 'Date', 
        'Year'              => 'Int'
    ];

    private static $has_one = [
        'SportManager'      => 'SportManager', 
        'SportAttorney'     => 'SportAttorney'
    ];
}

?>