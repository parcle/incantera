<?php

use Lame\Lame;
use Lame\Settings;

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 20/05/2017
 * Time: 11:40 PM
 */
class MySportAPI extends Controller {
 
    private static $allowed_actions = [
        'postMySportsList',
        'postAddMySports',
        'postGetMySportDetails',
        'postUpdateMySports',
        'postDeleteMySports'
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

    private function checkDataValidation($req, $response) {
        try {
            $sport_id           = $req->requestVar('sport_id');

            //Set default variable with default values
            $error_flg  = true;
            $messages   = [];

            if($sport_id =='') {
                $error_flg = false;
                $messages['sport_id'] = 'Sport Id is required field.';
            } else if(!$sport = Sport::get()->filter("ID", $sport_id)->first()) {
                $error_flg = false;
                $messages['sport_id'] = 'Sport Id is not valid value.';
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

    public function postMySportsList(SS_HTTPRequest $req) {
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

                $list = SportDetail::get()->filter('SportManagerID', $manager->ID);
            } else if($role == 'Attorney' ) {
                $attorney = SportAttorney::get()->filter('MemberID', $member->ID)->first();

                $list = SportDetail::get()->filter('SportManagerID', $attorney->ID);
            } else if($role == 'Trainer' ) {
                $trainer = Trainer::get()->filter('MemberID', $member->ID)->first();

                $list = SportDetail::get()->filter('SportManagerID', $trainer->ID);
            } else if($role == 'Athlete' ) {
                $athlete = Athlete::get()->filter('MemberID', $member->ID)->first();

                $list = SportDetail::get()->filter('SportManagerID', $athlete->ID);
            }
            if($list->count() > 0 ) {
                foreach($list AS $record) {
                    $sport_list[] = [
                        'ID'                => $record->ID,
                        'Sport'             => $record->Sport()->Name, 
                        'CurrentlyWorking'  => $record->CurrentlyWorking, 
                        'StartDate'         => date('d-m-Y', strtotime($record->StartDate)), 
                        'EndDate'           => date('d-m-Y', strtotime($record->EndDate))
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

    public function postAddMySports(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $sport_id           = $req->requestVar('sport_id');
            $currently_working  = $req->requestVar('currently_working');
            $start_date         = $req->requestVar('start_date');
            $end_date           = $req->requestVar('end_date');

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

                if(!$details = SportDetail::get()->filter( [ 'SportManagerID' => $manager->ID, 'SportID' => $sport_id ] )->first()) {
                    $details = SportDetail::create();
                }
                $details->SportID           = $sport_id;
                $details->SportManagerID    = $manager->ID;
                $details->CurrentlyWorking  = $currently_working;
                $details->StartDate         = date('Y-m-d', strtotime($start_date));
                $details->EndDate           = date('Y-m-d', strtotime($end_date));
                $details->write();
            } else if($role == 'Attorney' ) {
                //Before insert new record in SportDetail first we will check is user already have this sport or not.
                $attorney = SportAttorney::get()->filter('MemberID', $member->ID)->first();

                if(!$details = SportDetail::get()->filter( [ 'SportAttorneyID' => $attorney->ID, 'SportID' => $sport_id ] )->first()) {
                    $details = SportDetail::create();
                }
                $details->SportID           = $sport_id;
                $details->SportAttorneyID   = $attorney->ID;
                $details->CurrentlyWorking  = $currently_working;
                $details->StartDate         = date('Y-m-d', strtotime($start_date));
                $details->EndDate           = date('Y-m-d', strtotime($end_date));
                $details->write();
            } else if($role == 'Trainer' ) {
                //Before insert new record in SportDetail first we will check is user already have this sport or not.
                $trainer = Trainer::get()->filter('MemberID', $member->ID)->first();

                if(!$details = SportDetail::get()->filter( [ 'TrainerID' => $trainer->ID, 'SportID' => $sport_id ] )->first()) {
                    $details = SportDetail::create();
                }
                $details->SportID           = $sport_id;
                $details->TrainerID   = $trainer->ID;
                $details->CurrentlyWorking  = $currently_working;
                $details->StartDate         = date('Y-m-d', strtotime($start_date));
                $details->EndDate           = date('Y-m-d', strtotime($end_date));
                $details->write();
            } else if($role == 'Athlete' ) {
                //Before insert new record in SportDetail first we will check is user already have this sport or not.
                $athlete = Athlete::get()->filter('MemberID', $member->ID)->first();

                if(!$details = SportDetail::get()->filter( [ 'AthleteID' => $athlete->ID, 'SportID' => $sport_id ] )->first()) {
                    $details = SportDetail::create();
                }
                $details->SportID           = $sport_id;
                $details->AthleteID   = $athlete->ID;
                $details->CurrentlyWorking  = $currently_working;
                $details->StartDate         = date('Y-m-d', strtotime($start_date));
                $details->EndDate           = date('Y-m-d', strtotime($end_date));
                $details->write();

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

    public function postGetMySportDetails(SS_HTTPRequest $req) {
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

            if(!$record = SportDetail::get()->filter("ID", $record_id)->first()) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages'] = [ 'record_id' => 'Id which you passed in rquest is not valid. Please try agian.' ];
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $response['response_data']['sport_details'] = [
                        'ID'                => $record->ID,
                        'SportID'           => $record->SportID, 
                        'Sport'             => $record->Sport()->Name, 
                        'CurrentlyWorking'  => $record->CurrentlyWorking, 
                        'StartDate'         => date('d-m-Y', strtotime($record->StartDate)), 
                        'EndDate'           => date('d-m-Y', strtotime($record->EndDate))
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

    public function postUpdateMySports(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token              = $req->requestVar('_token');
            $record_id          = $req->requestVar('record_id');
            $sport_id           = $req->requestVar('sport_id');
            $currently_working  = $req->requestVar('currently_working');
            $start_date         = $req->requestVar('start_date');
            $end_date           = $req->requestVar('end_date');

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

            $record = SportDetail::get()->filter("ID", $record_id)->first();

            $record->SportID           = $sport_id;
            $record->CurrentlyWorking  = $currently_working;
            $record->StartDate         = date('Y-m-d', strtotime($start_date));
            $record->EndDate           = date('Y-m-d', strtotime($end_date));
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

    public function postDeleteMySports(SS_HTTPRequest $req) {
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

            if(!$record = SportDetail::get()->filter("ID", $record_id)->first()) {
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