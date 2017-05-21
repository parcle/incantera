<?php
class RESTMember extends DataExtension {

    private static $db = array (
        'MiddleName'        => 'Varchar(90)',
        'RESTToken'         => 'Varchar(64)',
        'RESTTokenExpiry'   => 'SS_DateTime',
        'PushToken'         => 'Varchar(255)',
        'Language'          => 'Varchar(2)',
        'HWID'              => 'Varchar(255)',
        'Timezone'          => 'Int',
        'DeviceType'        => 'Int',
        'MemberType'        => 'Enum(array("None", "Manager","Attorney","Trainer","Athlete","General"))'
    );

    public function createRestToken() {
        $token = md5(uniqid());
        while(Member::get()->filter("RESTToken", $token)->first()) {
            $token = md5(uniqid());
        }
        $this->owner->RESTToken = $token;
        $this->setTokenExpiry();
        $this->owner->write();
    }

    public function refreshRestToken() {
        $this->setTokenExpiry();
        $this->owner->write();
    }

    protected function setTokenExpiry($minutes = 1440) {
        $this->owner->RESTTokenExpiry = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
    }
        
    public function updateNotificationToken($token,$PushToken,$Language,$HWID,$Timezone,$DeviceType) {        
        $member = Member::get()->filter("RESTToken", $token)->first()   ;    
        $member->PushToken =  $PushToken ;
        $member->Language =  $Language ;
                $member->HWID = $HWID ;
                $member->Timezone =  $Timezone  ;
                $member->DeviceType =  $DeviceType  ;
                $member->write();
    }
}