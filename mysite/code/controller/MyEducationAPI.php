<?php

use Lame\Lame;
use Lame\Settings;

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 20/05/2017
 * Time: 11:40 PM
 */
class MyEducationAPI extends Controller {
 
    private static $allowed_actions = [
        'postEducationList', 
        'postAddEductionDetails', 
        'postEditEducationDetails', 
        'postUpdateEducationDetails', 
        'postDeleteEducationDetails'
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

    private function getUserRole($token) {
        try {
            $role = '';

            if($member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first()) {
                $role = $member->MemberType;
            }

            return $role;
        } catch (Exception $e) {
            $role = 'General';
            return $role;
        }
    }

    private function checkDataValidation($req, $response) {
        try {
            $degree_name            = $req->requestVar('degree_name');
            $college_name           = $req->requestVar('college_name');

            //Set default variable with default values
            $error_flg  = true;
            $messages   = [];

            if($degree_name =='') {
                $error_flg = false;
                $messages['degree_name'] = 'Degree Name is required field.';
            }

            if($college_name =='') {
                $error_flg = false;
                $messages['college_name'] = 'College Name is required field.';
            }

            //If error_flg is false then any of parameter is not valid then returns error messages with reason.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages']  = $messages;
            }

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred Data Validation process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }


    public function postEducationList(SS_HTTPRequest $req) {
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

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $role   = $this->getUserRole($token);

            $sport_list = [];
            if($role=='Manager') {
                $manager = SportManager::get()->filter('MemberID', $member->ID)->first();

                $list = EducationDetail::get()->filter('SportManagerID', $manager->ID);
            } else if($role == 'Attorney' ) {
                $attorney = SportAttorney::get()->filter('MemberID', $member->ID)->first();

                $list = EducationDetail::get()->filter('SportManagerID', $attorney->ID);
            } else if($role == 'Trainer' ) {
                $trainer = Trainer::get()->filter('MemberID', $member->ID)->first();

                $list = EducationDetail::get()->filter('SportManagerID', $trainer->ID);
            } else if($role == 'Athlete' ) {
                $athlete = Athlete::get()->filter('MemberID', $member->ID)->first();

                $list = EducationDetail::get()->filter('SportManagerID', $athlete->ID);
            }
            if($list->count() > 0 ) {
                foreach($list AS $record) {
                    $sport_list[] = [
                        'ID'            => $record->ID,
                        'DegreeName'    => $record->DegreeName, 
                        'CollegeName'   => $record->CollegeName, 
                        'PassoutYear'   => $record->PassoutYear, 
                        'PassClass'     => $record->PassClass, 
                        'Percentage'    => $record->Percentage
                    ];
                }
            }


            $response['response_data']['sport_list'] = $sport_list;

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

    public function postAddEductionDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $degree_name        = $req->requestVar('degree_name');
            $college_name       = $req->requestVar('college_name');
            $pass_year          = $req->requestVar('pass_year');
            $pass_class         = $req->requestVar('pass_class');
            $percentage         = $req->requestVar('percentage');

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

            //Check data validation of passed data
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $role   = $this->getUserRole($token);

            if( $role == 'Manager' ) {
                //Before insert new record in SportDetail first we will check is user already have this sport or not.
                $manager = SportManager::get()->filter('MemberID', $member->ID)->first();

                $details = EducationDetail::create();
                $details->SportManagerID    = $manager->ID;
            } else if($role == 'Attorney' ) {
                //Before insert new record in SportDetail first we will check is user already have this sport or not.
                $attorney = SportAttorney::get()->filter('MemberID', $member->ID)->first();

                $details = EducationDetail::create();
                $details->SportAttorneyID   = $attorney->ID;
            } else if($role == 'Trainer' ) {
                //Before insert new record in SportDetail first we will check is user already have this sport or not.
                $trainer = Trainer::get()->filter('MemberID', $member->ID)->first();

                $details = EducationDetail::create();
                $details->TrainerID   = $trainer->ID;
            } else if($role == 'Athlete' ) {
                //Before insert new record in SportDetail first we will check is user already have this sport or not.
                $athlete = Athlete::get()->filter('MemberID', $member->ID)->first();

                $details = EducationDetail::create();
                $details->AthleteID   = $athlete->ID;
            }
            $details->DegreeName        = $degree_name;
            $details->CollegeName       = $college_name;
            $details->PassoutYear       = $pass_year;
            $details->PassClass         = $pass_class;
            $details->Percentage        = $percentage;
            $details->write();

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

    public function postEditEducationDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $record_id          = $req->requestVar('record_id');

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

            if(!$record = EducationDetail::get()->filter("ID", $record_id)->first()) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages'] = [ 'record_id' => 'Id which you passed in rquest is not valid. Please try agian.' ];
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $response['response_data']['education_details'] = [
                        'ID'            => $record->ID,
                        'DegreeName'    => $record->DegreeName, 
                        'CollegeName'   => $record->CollegeName, 
                        'PassoutYear'   => $record->PassoutYear, 
                        'PassClass'     => $record->PassClass, 
                        'Percentage'    => $record->Percentage
                    ];

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

    public function postUpdateEducationDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $record_id          = $req->requestVar('record_id');
            $degree_name        = $req->requestVar('degree_name');
            $college_name       = $req->requestVar('college_name');
            $pass_year          = $req->requestVar('pass_year');
            $pass_class         = $req->requestVar('pass_class');
            $percentage         = $req->requestVar('percentage');

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

            //Check data validation of passed data
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();

            $record = EducationDetail::get()->filter("ID", $record_id)->first();
            $record->DegreeName        = $degree_name;
            $record->CollegeName       = $college_name;
            $record->PassoutYear       = $pass_year;
            $record->PassClass         = $pass_class;
            $record->Percentage        = $percentage;
            $record->write();

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

    public function postDeleteEducationDetails(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $record_id          = $req->requestVar('record_id');

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

            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();

            if(!$record = EducationDetail::get()->filter("ID", $record_id)->first()) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages'] = [ 'record_id' => 'Id which you passed in rquest is not valid. Please try agian.' ];
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $record->delete();

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