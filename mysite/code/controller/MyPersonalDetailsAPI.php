<?php

use Lame\Lame;
use Lame\Settings;

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 20/05/2017
 * Time: 11:40 PM
 */
class MyPersonalDetailsAPI extends Controller {
    private static $allowed_actions = [
        'postManagerDetails',
        'postUpdateManagerDetails',
        'postAttorneyDetails',
        'postUpdateAttorneyDetails',
        'postTrainerDetails',
        'postUpdateTrainerDetails',
        'postAthleteDetails',
        'postUpdateAthleteDetails',
        'postGeneralDetails',
        'postUpdateGeneralDetails'
    ];

    public function Link($action = null) {
        return Controller::join_links("personalapi", "v1", $action);
    }

    /**
     * @name        checkToken
     * @param       $token
     * @param       $response
     * @return      mixed
     * @description This function is used to check token validation.
     */
    private function checkToken($token, $response){
        try {
            //Check for token is blank or not.
            if($token == '') {
                $response['error_reason']   = 'InvalidToken';
                $response['error_messages'] = 'You must provide a token.';
                return $response;
            }

            //Get the member details related to passed token
            if(!$member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first()) {
                $response['error_reason']   = 'InvalidToken';
                $response['error_messages'] = 'You must provide a valid token.';
                return $response;
            }

            //Check the token is expired or not.
            if($member->obj('RESTTokenExpiry')->InPast()) {
                $response['error_reason']   = 'InvalidToken';
                $response['error_messages'] = 'Token is expired.';
                return $response;
            }

            //token is valid so update the token time.
            $member->refreshRestToken();

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred token validation process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    private function checkRole($token, $response, $role) {
        try {

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();

            if($member->MemberType != $role) {
                $response['error_reason']   = 'RoleConflict';
                return $response;
            }

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred Role validation process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    private function getCountryList() {
        $country_list = [];
        try {
            $list = Country::get()->sort('Name');
            if($list->count() > 0) {
                foreach($list AS $record) {
                    $country_list[$record->ID] = $record->Name;
                }
            }

            return $country_list;
        } catch (Exception $e) {
            return $country_list;
        }
    }

    private function getAthleteLevelList() {
        $level_list = [];
        try {
            $list = AthleteLevel::get()->sort('Label');
            if($list->count() > 0) {
                foreach($list AS $record) {
                    $level_list[$record->ID] = $record->Label;
                }
            }

            return $level_list;
        } catch (Exception $e) {
            return $level_list;
        }
    }

    private function getGenderList() {
        $gender_list = [
            'Male'      => 'Male',
            'Female'    => 'Female',
            'Unknown'   => 'Not to Reveal'
        ];

        return $gender_list;
    }

    private function getWorkForList() {
        $work_list = [
            'Team'      => 'Team',
            'Athlete'   => 'Athlete',
            'Both'      => 'Both',
            'None'      => 'Not to Reveal'
        ];

        return $work_list;
    }

    private function getSalaryList() {
        $salary_list = [
            'Fixed'         => 'Fixed',
            'Commision'     => 'Commision',
            'Hybrid'        => 'Hybrid',
            'None'          => 'Not to Reveal'
        ];

        return $salary_list;
    }

    private function getSportList() {
        $sport_list = [];
        try {
            $list = Sport::get()->sort('Name');
            if($list->count() > 0) {
                foreach($list AS $record) {
                    $sport_list[$record->ID] = $record->Name;
                }
            }

            return $sport_list;
        } catch (Exception $e) {
            return $sport_list;
        }
    }

    private function checkGeneralDataValidation($req, $response) {
        try {
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $country_id         = $req->requestVar('country_id');

            //Set default variable with default values
            $error_flg  = true;
            $messages   = [];

            //Check validation of all paramters
            if($address=='') {
                $error_flg = false;
                $messages['address'] = 'Address is required field.';
            }
            if($city=='') {
                $error_flg = false;
                $messages['city'] = 'City is required field.';
            }
            if($zipcode=='') {
                $error_flg = false;
                $messages['zipcode'] = 'Zipcode is required field.';
            }
            if($country_id=='') {
                $error_flg = false;
                $messages['country_id'] = 'Country Id is required field.';
            } else if(!$country = Country::get()->filter("ID", $country_id)->first()) {
                $error_flg = false;
                $messages['country_id'] = 'Country Id is not valid value.';
            }

            //If error_flg is false then any of parameter is not valid then returns error messages with reason.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_message']  = $messages;
            }

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred Data Validation process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    public function checkTrainerDataValidation($req, $response) {
        try {
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $official_email     = $req->requestVar('official_email');
            $country_id         = $req->requestVar('country_id');
            $sport_id           = $req->requestVar('sport_id');
            $level_id           = $req->requestVar('level_id');

            //Set default variable with default values
            $error_flg  = true;
            $messages   = [];

            //Check validation of all paramters
            if($address=='') {
                $error_flg = false;
                $messages['address'] = 'Address is required field.';
            }
            if($city=='') {
                $error_flg = false;
                $messages['city'] = 'City is required field.';
            }
            if($zipcode=='') {
                $error_flg = false;
                $messages['zipcode'] = 'Zipcode is required field.';
            }
            if($country_id=='') {
                $error_flg = false;
                $messages['country_id'] = 'Country Id is required field.';
            } else if(!$country = Country::get()->filter("ID", $country_id)->first()) {
                $error_flg = false;
                $messages['country_id'] = 'Country Id is not valid value.';
            }
            if($official_email=='') {
                $error_flg = false;
                $messages['official_email'] = 'Official Email is required field.';
            } else if(!filter_var($official_email, FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\./', $official_email)) {
                $error_flg = false;
                $messages['official_email'] = 'Official Email is not in valid format.';
            }
            if($sport_id =='') {
                $error_flg = false;
                $messages['sport_id'] = 'Sport is required field.';
            } else if(!$sport = Sport::get()->filter("ID", $sport_id)->first()) {
                $error_flg = false;
                $messages['sport_id'] = 'Sport Id is not valid value.';
            }
            if($level_id=='') {
                $error_flg = false;
                $messages['level_id'] = 'Athlete Level is required field.';
            } else if(!$level = AthleteLevel::get()->filter("ID", $level_id)->first()) {
                $error_flg = false;
                $messages['level_id'] = 'Athlete Level Id is not valid value.';
            }

            //If error_flg is false then any of parameter is not valid then returns error messages with reason.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_message']  = $messages;
            }

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred Data Validation process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    private function checkManagerDataValidation($req, $response) {
        try {
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $official_email     = $req->requestVar('official_email');
            $position           = $req->requestVar('position');
            $country_id         = $req->requestVar('country_id');

            //Set default variable with default values
            $error_flg  = true;
            $messages   = [];

            //Check validation of all paramters
            if($address=='') {
                $error_flg = false;
                $messages['address'] = 'Address is required field.';
            }
            if($city=='') {
                $error_flg = false;
                $messages['city'] = 'City is required field.';
            }
            if($zipcode=='') {
                $error_flg = false;
                $messages['zipcode'] = 'Zipcode is required field.';
            }
            if($position=='') {
                $error_flg = false;
                $messages['position'] = 'Position is required field.';
            }
            if($country_id=='') {
                $error_flg = false;
                $messages['country_id'] = 'Country Id is required field.';
            } else if(!$country = Country::get()->filter("ID", $country_id)->first()) {
                $error_flg = false;
                $messages['country_id'] = 'Country Id is not valid value.';
            }
            if($official_email=='') {
                $error_flg = false;
                $messages['official_email'] = 'Official Email is required field.';
            } else if(!filter_var($official_email, FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\./', $official_email)) {
                $error_flg = false;
                $messages['official_email'] = 'Official Email is not in valid format.';
            }

            //If error_flg is false then any of parameter is not valid then returns error messages with reason.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_message']  = $messages;
            }

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred Data Validation process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    public function postManagerDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Check member role
            $response = $this->checkRole($token, $response, 'Manager');

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                $response['error_messages'] = 'Oops!! Looks like you are requesting wrong URL. This request is allow only for Sport Manager. Please try again';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $manager = SportManager::get()->filter([ 'MemberID' => $member->ID ])->first();

            $response['response_data']['personal_data'] = [
                'Title'             => $manager->Title,
                'BusineesName'      => $manager->BusinessName,
                'Gender'            => @($manager->Gender=='None')?'':($manager->Gender),
                'BirthDate'         => $manager->BirthDate,
                'MarritalStatus'    => @($manager->MarritalStatus=='None')?'':($manager->MarritalStatus),
                'MarriageDate'      => $manager->MarriageDate,
                'WebsiteURL'        => $manager->WebsiteURL,
                'Address'           => $manager->Address,
                'City'              => $manager->City,
                'Zipcode'           => $manager->Zipcode,
                'OfficialEmail'     => $manager->OfficialEmail,
                'OfficialContactNo' => $manager->OfficialContactNo,
                'OfficialMobile'    => $manager->OfficialMobile,
                'JobSummary'        => $manager->JobSummary,
                'JobDescription'    => $manager->JobDescription,
                'Position'          => $manager->Position,
                'WorkFor'           => @($manager->WorkFor=='None')?'':($manager->WorkFor),
                'Salary'            => @($manager->Salary=='None')?'':($manager->Salary),
                'Country'           => $manager->Country()->Name
            ];

            $response['response_data']['country_list']  = $this->getCountryList();
            $response['response_data']['gender_list']   = $this->getGenderList();
            $response['response_data']['work_list']     = $this->getWorkForList();
            $response['response_data']['salary_list']   = $this->getSalaryList();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postUpdateManagerDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $title              = $req->requestVar('title');
            $business_name      = $req->requestVar('business_name');
            $gender             = $req->requestVar('gender');
            $birth_date         = $req->requestVar('birth_date');
            $marrital_status    = $req->requestVar('marrital_status');
            $marriage_date      = $req->requestVar('marriage_date');
            $website_url        = $req->requestVar('website_url');
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $official_email     = $req->requestVar('official_email');
            $official_contact   = $req->requestVar('official_contact');
            $official_mobile    = $req->requestVar('official_mobile');
            $job_summary        = $req->requestVar('job_summary');
            $job_description    = $req->requestVar('job_description');
            $position           = $req->requestVar('position');
            $work_for           = $req->requestVar('work_for');
            $salary             = $req->requestVar('salary');
            $country_id         = $req->requestVar('country_id');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //First check the validation of passed data
            $response = $this->checkManagerDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member     = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $manager    = SportManager::get()->filter([ 'MemberID' => $member->ID ])->first();

            $manager->Title             = $title;
            $manager->BusinessName      = $business_name;
            $manager->Gender            = $gender;
            $manager->BirthDate         = date('Y-m-d', strtotime($birth_date));
            $manager->MarritalStatus    = $marrital_status;
            $manager->MarriageDate      = date('Y-m-d', strtotime($marriage_date));
            $manager->WebsiteURL        = $website_url;
            $manager->Address           = $address;
            $manager->City              = $city;
            $manager->Zipcode           = $zipcode;
            $manager->OfficialEmail     = $official_email;
            $manager->OfficialContactNo = $official_contact;
            $manager->OfficialMobile    = $official_mobile;
            $manager->JobSummary        = $job_summary;
            $manager->JobDescription    = $job_description;
            $manager->Position          = $position;
            $manager->WorkFor           = $work_for;
            $manager->Salary            = $salary;
            $manager->CountryID         = $country_id;

            $manager->write();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postAttorneyDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Check member role
            $response = $this->checkRole($token, $response, 'Attorney');

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                $response['error_messages'] = 'Oops!! Looks like you are requesting wrong URL. This request is allow only for Sport Attorneys. Please try again';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $attorney = SportAttorney::get()->filter([ 'MemberID' => $member->ID ])->first();

            $response['response_data']['personal_data'] = [
                'Title'             => $attorney->Title,
                'BusineesName'      => $attorney->BusinessName,
                'Gender'            => @($attorney->Gender=='None')?'':($attorney->Gender),
                'BirthDate'         => $attorney->BirthDate,
                'MarritalStatus'    => @($attorney->MarritalStatus=='None')?'':($attorney->MarritalStatus),
                'MarriageDate'      => $attorney->MarriageDate,
                'WebsiteURL'        => $attorney->WebsiteURL,
                'Address'           => $attorney->Address,
                'City'              => $attorney->City,
                'Zipcode'           => $attorney->Zipcode,
                'OfficialEmail'     => $attorney->OfficialEmail,
                'OfficialContactNo' => $attorney->OfficialContactNo,
                'OfficialMobile'    => $attorney->OfficialMobile,
                'JobSummary'        => $attorney->JobSummary,
                'JobDescription'    => $attorney->JobDescription,
                'Position'          => $attorney->Position,
                'WorkFor'           => @($attorney->WorkFor=='None')?'':($attorney->WorkFor),
                'Salary'            => @($attorney->Salary=='None')?'':($attorney->Salary),
                'Country'           => $attorney->Country()->Name
            ];

            $response['response_data']['country_list']  = $this->getCountryList();
            $response['response_data']['gender_list']   = $this->getGenderList();
            $response['response_data']['work_list']     = $this->getWorkForList();
            $response['response_data']['salary_list']   = $this->getSalaryList();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postUpdateAttorneyDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $title              = $req->requestVar('title');
            $business_name      = $req->requestVar('business_name');
            $gender             = $req->requestVar('gender');
            $birth_date         = $req->requestVar('birth_date');
            $marrital_status    = $req->requestVar('marrital_status');
            $marriage_date      = $req->requestVar('marriage_date');
            $website_url        = $req->requestVar('website_url');
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $official_email     = $req->requestVar('official_email');
            $official_mobile    = $req->requestVar('official_mobile');
            $official_contact   = $req->requestVar('official_contact');
            $job_summary        = $req->requestVar('job_summary');
            $job_description    = $req->requestVar('job_description');
            $position           = $req->requestVar('position');
            $work_for           = $req->requestVar('work_for');
            $salary             = $req->requestVar('salary');
            $country_id         = $req->requestVar('country_id');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //First check the validation of passed data
            $response = $this->checkManagerDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member     = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $attorney   = SportAttorney::get()->filter([ 'MemberID' => $member->ID ])->first();

            $attorney->Title                = $title;
            $attorney->BusinessName         = $business_name;
            $attorney->Gender               = $gender;
            $attorney->BirthDate            = date('Y-m-d', strtotime($birth_date));
            $attorney->MarritalStatus       = $marrital_status;
            $attorney->MarriageDate         = date('Y-m-d', strtotime($marriage_date));
            $attorney->WebsiteURL           = $website_url;
            $attorney->Address              = $address;
            $attorney->City                 = $city;
            $attorney->Zipcode              = $zipcode;
            $attorney->OfficialEmail        = $official_email;
            $attorney->OfficialContactNo    = $official_contact;
            $attorney->OfficialMobile       = $official_mobile;
            $attorney->JobSummary           = $job_summary;
            $attorney->JobDescription       = $job_description;
            $attorney->Position             = $position;
            $attorney->WorkFor              = $work_for;
            $attorney->Salary               = $salary;
            $attorney->CountryID            = $country_id;

            $attorney->write();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postTrainerDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Check member role
            $response = $this->checkRole($token, $response, 'Trainer');

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                $response['error_messages'] = 'Oops!! Looks like you are requesting wrong URL. This request is allow only for Sport Trainer. Please try again';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $trainer = Trainer::get()->filter([ 'MemberID' => $member->ID ])->first();

            $response['response_data']['personal_data'] = [
                'Title'             => $trainer->Title,
                'BusineesName'      => $trainer->BusinessName,
                'Gender'            => @($trainer->Gender=='None')?'':($trainer->Gender),
                'BirthDate'         => $trainer->BirthDate,
                'MarritalStatus'    => @($trainer->MarritalStatus=='None')?'':($trainer->MarritalStatus),
                'MarriageDate'      => $trainer->MarriageDate,
                'WebsiteURL'        => $trainer->WebsiteURL,
                'Address'           => $trainer->Address,
                'City'              => $trainer->City,
                'Zipcode'           => $trainer->Zipcode,
                'OfficialEmail'     => $trainer->OfficialEmail,
                'OfficialContactNo' => $trainer->OfficialContactNo,
                'OfficialMobile'    => $trainer->OfficialMobile,
                'JobSummary'        => $trainer->JobSummary,
                'JobDescription'    => $trainer->JobDescription,
                'Position'          => $trainer->Position,
                'WorkFor'           => @($trainer->WorkFor=='None')?'':($trainer->WorkFor),
                'Country'           => $trainer->Country()->Name
            ];

            $response['response_data']['country_list']  = $this->getCountryList();
            $response['response_data']['gender_list']   = $this->getGenderList();
            $response['response_data']['work_list']     = $this->getWorkForList();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postUpdateTrainerDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $title              = $req->requestVar('title');
            $business_name      = $req->requestVar('business_name');
            $gender             = $req->requestVar('gender');
            $birth_date         = $req->requestVar('birth_date');
            $marrital_status    = $req->requestVar('marrital_status');
            $marriage_date      = $req->requestVar('marriage_date');
            $website_url        = $req->requestVar('website_url');
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $official_email     = $req->requestVar('official_email');
            $official_mobile    = $req->requestVar('official_mobile');
            $official_contact   = $req->requestVar('official_contact');
            $job_summary        = $req->requestVar('job_summary');
            $job_description    = $req->requestVar('job_description');
            $position           = $req->requestVar('position');
            $work_for           = $req->requestVar('work_for');
            $salary             = $req->requestVar('salary');
            $country_id         = $req->requestVar('country_id');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //First check the validation of passed data
            $response = $this->checkManagerDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member     = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $trainer    = Trainer::get()->filter([ 'MemberID' => $member->ID ])->first();

            $trainer->Title                = $title;
            $trainer->BusinessName         = $business_name;
            $trainer->Gender               = $gender;
            $trainer->BirthDate            = date('Y-m-d', strtotime($birth_date));
            $trainer->MarritalStatus       = $marrital_status;
            $trainer->MarriageDate         = date('Y-m-d', strtotime($marriage_date));
            $trainer->WebsiteURL           = $website_url;
            $trainer->Address              = $address;
            $trainer->City                 = $city;
            $trainer->Zipcode              = $zipcode;
            $trainer->OfficialEmail        = $official_email;
            $trainer->OfficialContactNo    = $official_contact;
            $trainer->OfficialMobile       = $official_mobile;
            $trainer->JobSummary           = $job_summary;
            $trainer->JobDescription       = $job_description;
            $trainer->Position             = $position;
            $trainer->WorkFor              = $work_for;
            $trainer->Salary               = $salary;
            $trainer->CountryID            = $country_id;

            $trainer->write();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postAthleteDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Check member role
            $response = $this->checkRole($token, $response, 'Athlete');

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                $response['error_messages'] = 'Oops!! Looks like you are requesting wrong URL. This request is allow only for Athlete. Please try again';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $athlete = Athlete::get()->filter([ 'MemberID' => $member->ID ])->first();

            $response['response_data']['personal_data'] = [
                'ID'                => $athlete->ID,
                'Title'             => $athlete->Title,
                'BusinessName'      => $athlete->BusinessName,
                'Gender'            => @($athlete->Gender=='None')?'':($athlete->Gender),
                'BirthDate'         => $athlete->BirthDate,
                'MarritalStatus'    => @($athlete->MarritalStatus=='None')?'':($athlete->MarritalStatus),
                'MarriageDate'      => $athlete->MarriageDate,
                'WebsiteURL'        => $athlete->WebsiteURL,
                'Address'           => $athlete->Address,
                'City'              => $athlete->City,
                'Zipcode'           => $athlete->Zipcode,
                'OfficialEmail'     => $athlete->OfficialEmail,
                'OfficialContactNo' => $athlete->OfficialContactNo,
                'OfficialMobile'    => $athlete->OfficialMobile,
                'JobSummary'        => $athlete->JobSummary,
                'JobDescription'    => $athlete->JobDescription,
                'Position'          => $athlete->Position,
                'Country'           => $athlete->Country()->Name,
                'Sport'             => $athlete->Sport()->Name,
                'AthleteLevel'      => $athlete->AthleteLevel()->Label
            ];

            $response['response_data']['country_list']  = $this->getCountryList();
            $response['response_data']['gender_list']   = $this->getGenderList();
            $response['response_data']['sport_list']    = $this->getSportList();
            $response['response_data']['level_list']    = $this->getAthleteLevelList();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postUpdateAthleteDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $title              = $req->requestVar('title');
            $business_name      = $req->requestVar('business_name');
            $gender             = $req->requestVar('gender');
            $birth_date         = $req->requestVar('birth_date');
            $marrital_status    = $req->requestVar('marrital_status');
            $marriage_date      = $req->requestVar('marriage_date');
            $website_url        = $req->requestVar('website_url');
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $official_email     = $req->requestVar('official_email');
            $official_contact   = $req->requestVar('official_contact');
            $official_mobile    = $req->requestVar('official_mobile');
            $job_summary        = $req->requestVar('job_summary');
            $job_description    = $req->requestVar('job_description');
            $position           = $req->requestVar('position');
            $country_id         = $req->requestVar('country_id');
            $sport_id           = $req->requestVar('sport_id');
            $level_id           = $req->requestVar('level_id');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Check data validation
            $response = $this->checkTrainerDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $athlete = Athlete::get()->filter([ 'MemberID' => $member->ID ])->first();

            $athlete->Title             = $title;
            $athlete->BusinessName      = $business_name;
            $athlete->Gender            = $gender;
            $athlete->BirthDate         = date('Y-m-d', strtotime($birth_date));
            $athlete->MarritalStatus    = $marrital_status;
            $athlete->MarriageDate      = date('Y-m-d', strtotime($marriage_date));
            $athlete->WebsiteURL        = $website_url;
            $athlete->Address           = $address;
            $athlete->City              = $city;
            $athlete->Zipcode           = $zipcode;
            $athlete->OfficialEmail     = $official_email;
            $athlete->OfficialContactNo = $official_contact;
            $athlete->OfficialMobile    = $official_mobile;
            $athlete->JobSummary        = $job_summary;
            $athlete->JobDescription    = $job_description;
            $athlete->Position          = $position;
            $athlete->CountryID         = $country_id;
            $athlete->SportID           = $sport_id;
            $athlete->AthleteLevelID    = $level_id;

            $athlete->write();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postGeneralDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Check member role
            $response = $this->checkRole($token, $response, 'General');

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                $response['error_messages'] = 'Oops!! Looks like you are requesting wrong URL. This request is allow only for General User. Please try again';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $general = GeneralUser::get()->filter([ 'MemberID' => $member->ID ])->first();

            $response['response_data']['personal_data'] = [
                'ID'                => $general->ID,
                'Gender'            => @($general->Gender=='None')?'':($general->Gender),
                'BirthDate'         => $general->BirthDate,
                'MarritalStatus'    => @($general->MarritalStatus=='None')?'':($general->MarritalStatus),
                'MarriageDate'      => $general->MarriageDate,
                'Address'           => $general->Address,
                'City'              => $general->City,
                'Zipcode'           => $general->Zipcode,
                'Description'       => $general->Description,
                'Country'           => $general->Country()->Name
            ];

            $response['response_data']['country_list']  = $this->getCountryList();
            $response['response_data']['gender_list']   = $this->getGenderList();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

    public function postUpdateGeneralDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $gender             = $req->requestVar('gender');
            $birth_date         = $req->requestVar('birth_date');
            $marrital_status    = $req->requestVar('marrital_status');
            $marriage_date      = $req->requestVar('marriage_date');
            $address            = $req->requestVar('address');
            $city               = $req->requestVar('city');
            $zipcode            = $req->requestVar('zipcode');
            $country_id         = $req->requestVar('country_id');
            $description        = $req->requestVar('description');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Check data validation
            $response = $this->checkGeneralDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $general = GeneralUser::get()->filter([ 'MemberID' => $member->ID ])->first();

            $general->Gender            = $gender;
            $general->BirthDate         = date('Y-m-d', strtotime($birth_date));
            $general->MarritalStatus    = $marrital_status;
            $general->MarriageDate      = date('Y-m-d', strtotime($marriage_date));
            $general->Address           = $address;
            $general->City              = $city;
            $general->Zipcode           = $zipcode;
            $general->Description       = $description;
            $general->CountryID         = $country_id;

            $general->write();

            //Set process_status = true and returns success response in json format.
            $response['process_status'] = true;
            return new SS_HTTPResponse(Convert::array2json($response), 200);

        } catch (Exception $e) {

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'                  => 'UnknownError',
                'error_messages'                => 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.',
                'response_data'                 => [] ];

            return new SS_HTTPResponse(Convert::array2json($response), 400);
        }
    }

}