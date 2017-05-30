<?php

use Lame\Lame;
use Lame\Settings;

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 20/05/2017
 * Time: 11:40 PM
 */
class MyAthleteRequestAPI extends Controller {
 
    private static $allowed_actions = [
        'postGetAthleteRequests', 
        'postSendRequestToAthlete', 
        'postDeleteAthlete'
    ];

    public function Link($action = null) {
        return Controller::join_links("myexperienceapi", "v1", $action);
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
            $athlete_id = $req->requestVar('athlete_id');

            $error_flg  = true;
            $messages   = [];

            if($athlete_id =='') {
                $error_flg = false;
                $messages['athlete_id'] = 'Athlete is required field.';
            } else if(!$athlete = Athlete::get()->filter("ID", $athlete_id)->first()) {
                $error_flg = false;
                $messages['athlete_id'] = 'Athlete is not valid value.';
            }

            //If error_flg is false then any of parameter is not valid then returns error messages with reason.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages']  = $messages;
            }

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred data validation process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    public function postGetAthleteRequests(SS_HTTPRequest $req) {
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

            //Get Member record and Role of user.
            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $role   = $this->getUserRole($token);

            //Get list of record from Role of user.
            $education_list = [];
            if($role=='Manager') {
                $manager = SportManager::get()->filter('MemberID', $member->ID)->first();

                $list = AthleteRequest::get()->filter('SportManagerID', $manager->ID);
            } else if($role == 'Attorney' ) {
                $attorney = SportAttorney::get()->filter('MemberID', $member->ID)->first();

                $list = AthleteRequest::get()->filter('SportManagerID', $attorney->ID);
            } else if($role == 'Trainer' ) {
                $trainer = Trainer::get()->filter('MemberID', $member->ID)->first();

                $list = AthleteRequest::get()->filter('SportManagerID', $trainer->ID);
            } else if($role == 'Athlete' ) {
                $response['error_reason']   = 'AccessDenied';
                $response['error_messages'] = 'You cannot have Request feature. It is only allow for Sport Manager, Sport Attorney or Trainer.';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Loop through list of records and create array as we required.
            if($list->count() > 0 ) {
                foreach($list AS $record) {
                    $athlete = Athlete::get()->filter(['ID' => $record->AthleteID ])->first();
                    $name = @($athlete)?($athlete->Member()->FirstName." ".$athlete->Member()->Surname):'';

                    $request_list[] = [
                        'ID'                => $record->ID,
                        'Athlete'           => $name,  
                        'RequestMessage'    => $record->RequestMessage, 
                        'Status'            => $record->Status, 
                        'BlockStatus'       => $record->BlockStatus, 
                        'ResponseMessage'   => $record->ResponseMessage, 
                        'Created'           => date('d-m-Y H:i', strtotime($record->Created))
                    ];
                }
            }

            //Store result array in response data array.
            $response['response_data']['request_list'] = $request_list;

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
    
    public function postSendRequestToAthlete(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $athlete_id     = $req->requestVar('athlete_id');
            $message        = $req->requestVar('message');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of Token
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //check validation of passed data.
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Get Member record and Role of user.
            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();
            $role   = $this->getUserRole($token);

            //Get list of record from Role of user.
            $education_list = [];
            if($role=='Manager') {
                $manager = SportManager::get()->filter('MemberID', $member->ID)->first();

                //Check is request is already send or not. If already sended then just update details else create new.
                if(!$request = AthleteRequest::get()->filter( [ 'SportManagerID' => $manager->ID, 'AthleteID' => $athlete_id ] )->first()) {
                    $request = AthleteRequest::create();
                } else {
                    $response['error_messages'] = 'You already have request sent in past. Just re-send it again.';
                }

                $request->SportManagerID = $manager->ID;
            } else if($role == 'Attorney' ) {
                $attorney = SportAttorney::get()->filter('MemberID', $member->ID)->first();


                //Check is request is already send or not. If already sended then just update details else create new.
                if(!$request = AthleteRequest::get()->filter( [ 'SportAttorneyID' => $attorney->ID, 'AthleteID' => $athlete_id ] )->first()) {
                    $request = AthleteRequest::create();
                } else {
                    $response['error_messages'] = 'You already have request sent in past. Just re-send it again.';
                }

                $request->SportAttorneyID = $attorney->ID;
            } else if($role == 'Trainer' ) {
                $trainer = Trainer::get()->filter('MemberID', $member->ID)->first();

                //Check is request is already send or not. If already sended then just update details else create new.
                if(!$request = AthleteRequest::get()->filter( [ 'TrainerID' => $trainer->ID, 'AthleteID' => $athlete_id ] )->first()) {
                    $request = AthleteRequest::create();
                } else {
                    $response['error_messages'] = 'You already have request sent in past. Just re-send it again.';
                }

                $request->TrainerID = $trainer->ID;
            } else if($role == 'Athlete' ) {
                $response['error_reason']   = 'AccessDenied';
                $response['error_messages'] = 'You cannot have Request feature. It is only allow for Sport Manager, Sport Attorney or Trainer.';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $request->RequestMessage = $message;
            $request->AthleteID     = $athlete_id;
            $request->Status        = 'Waiting';
            $request->write();

            $status = AthleteRequestStatusDetail::create();
            $status->OldStatus = 'Waiting';
            $status->NewStatus = 'Waiting';
            $status->AthleteRequestID = $request->ID;
            $status->write();

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
    
    public function postDeleteAthlete(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $record_id     = $req->requestVar('record_id');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of Token
            $response = $this->checkToken($token, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            if(!$record = AthleteRequest::get()->filter("ID", $record_id)->first()) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages'] = [ 'record_id' => 'Id which you passed in request is not valid. Please try agian.' ];
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $record->delete();

            $query = new SQLQuery();
            $query->setFrom('AthleteRequestStatusDetail');
            $query->addWhere("AthleteRequestID = '".$record_id."'");
            $query->setDelete(true);

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