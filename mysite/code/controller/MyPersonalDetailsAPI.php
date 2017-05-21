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
            $token          = $req->requestVar('_token');
            $role           = $req->requestVar('role');

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
            $token          = $req->requestVar('_token');
            $role           = $req->requestVar('role');

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
            $token          = $req->requestVar('_token');
            $role           = $req->requestVar('role');

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
                'Title'             => $athlete->Title,
                'BusineesName'      => $athlete->BusinessName,
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
                'Country'           => $athlete->Country()->Name
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

    public function postUpdateAthleteDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $role           = $req->requestVar('role');

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
                'Gender'            => @($general->Gender=='None')?'':($general->Gender),
                'BirthDate'         => $general->BirthDate,
                'MarritalStatus'    => @($general->MarritalStatus=='None')?'':($general->MarritalStatus),
                'MarriageDate'      => $general->MarriageDate,
                'Address'           => $general->Address,
                'City'              => $general->City,
                'Zipcode'           => $general->Zipcode,
                'Description'       => $general->OfficialEmail,
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
            $token          = $req->requestVar('_token');
            $role           = $req->requestVar('role');

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