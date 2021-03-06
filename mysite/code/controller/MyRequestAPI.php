<?php

use Lame\Lame;
use Lame\Settings;

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 20/05/2017
 * Time: 11:40 PM
 */
class MyRequestAPI extends Controller {
 
    private static $allowed_actions = [
        'postMyRequestsList', 
        'postAcceptRequests', 
        'postRejectRequests', 
        'postBlockRequests', 
        'postReportAbuseRequests'
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
            $request_id = $req->requestVar('request_id');

            $error_flg  = true;
            $messages   = [];

            if($request_id =='') {
                $error_flg = false;
                $messages['request_id'] = 'Request ID is required field.';
            } else if(!$request = AthleteRequest::get()->filter("ID", $request_id)->first()) {
                $error_flg = false;
                $messages['request_id'] = 'Request ID is not valid value.';
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

    public function postMyRequestsList(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $filter         = $req->requestVar('filter');

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
            $athlete = Athlete::get()->filter('MemberID', $member->ID)->first();

            $list = AthleteRequest::get()->filter('AthleteID', $athlete->ID);
            if($list->count() > 0) {
                foreach ($list as $record) {
                    $name = '';
                    $user_type = '';
                    $manager = SportManager::get()->filter(['ID' => $record->SportManagerID ])->first();
                    if($record->SportManagerID != 0 && $record->SportManagerID != '' && $record->SportManagerID != null) {
                        $name = @($manager)?($manager->Member()->FirstName." ".$manager->Member()->Surname):'';
                        $user_type = 'Sport Manager';
                    }

                    $attorney = SportAttorney::get()->filter(['ID' => $record->SportAttorneyID ])->first();
                    if($record->SportAttorneyID != 0 && $record->SportAttorneyID != '' && $record->SportAttorneyID != null) {
                        $name = @($attorney)?($attorney->Member()->FirstName." ".$attorney->Member()->Surname):'';
                        $user_type = 'Sport Attorney';
                    }

                    $trainer = Trainer::get()->filter(['ID' => $record->TrainerID ])->first();
                    if($record->TrainerID != 0 && $record->TrainerID != '' && $record->TrainerID != null) {
                        $name = @($trainer)?($trainer->Member()->FirstName." ".$trainer->Member()->Surname):'';
                        $user_type = 'Trainer';
                    }

                    $status_list = [];
                    foreach($record->RequestStatus() AS $status_record) {
                        $status_list[] = [
                                    'OldStatus' => $status_record->OldStatus, 
                                    'NewStatus' => $status_record->NewStatus, 
                                    'ChangeOn'  => date('d-m-Y H:i', strtotime($status_record->Created))
                        ];
                    }

                    $request_list[] = [
                        'ID'                => $record->ID,
                        'UserType'          => $user_type,
                        'User'              => $name,  
                        'RequestMessage'    => $record->RequestMessage, 
                        'Status'            => $record->Status, 
                        'BlockStatus'       => $record->BlockStatus, 
                        'ResponseMessage'   => $record->ResponseMessage, 
                        'Created'           => date('d-m-Y H:i', strtotime($record->Created)), 
                        'StatusHistory'     => $status_list
                    ];
                }
            }

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
    
    public function postAcceptRequests(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $request_id     = $req->requestVar('request_id');
            $message        = $req->requestVar('message');

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
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $request = AthleteRequest::get()->filter("ID", $request_id)->first();
            $old_status = $request->Status;

            if($request->Status=='Approved') {
                $response['error_messages'] = 'This request is already approved.';
            } else {
                $request->Status            = 'Approved';
                $request->ResponseMessage   = $message;
                $request->write();

                $status = AthleteRequestStatusDetail::create();
                $status->OldStatus = $old_status;
                $status->NewStatus = 'Approved';
                $status->AthleteRequestID = $request->ID;
                $status->write();
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
    
    public function postRejectRequests(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $request_id     = $req->requestVar('request_id');
            $message        = $req->requestVar('message');

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
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $request = AthleteRequest::get()->filter("ID", $request_id)->first();
            $old_status = $request->Status;

            if($request->Status=='Rejected') {
                $response['error_messages'] = 'This request is already Rejected.';
            } else {
                $request->Status            = 'Rejected';
                $request->ResponseMessage   = $message;
                $request->write();

                $status = AthleteRequestStatusDetail::create();
                $status->OldStatus = $old_status;
                $status->NewStatus = 'Rejected';
                $status->AthleteRequestID = $request->ID;
                $status->write();
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
    
    public function postBlockRequests(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $request_id     = $req->requestVar('request_id');
            $message        = $req->requestVar('message');

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
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $request = AthleteRequest::get()->filter("ID", $request_id)->first();
            $old_status = $request->Status;

            if($request->BlockStatus=='Yes') {
                $response['error_messages'] = 'This request is already Blocked.';
            } else {
                $request->Status            = 'Rejected';
                $request->BlockStatus       = 'Yes';
                $request->ResponseMessage   = $message;
                $request->write();

                $status = AthleteRequestStatusDetail::create();
                $status->OldStatus = $old_status;
                $status->NewStatus = 'Block';
                $status->AthleteRequestID = $request->ID;
                $status->write();
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
    
    public function postReportAbuseRequests(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $request_id     = $req->requestVar('request_id');
            $message        = $req->requestVar('message');

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
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $request = AthleteRequest::get()->filter("ID", $request_id)->first();
            $old_status = $request->Status;

            if($request->AbuseStatus=='Yes') {
                $response['error_messages'] = 'This request is already Blocked.';
            } else {
                $request->Status            = 'Rejected';
                $request->AbuseStatus       = 'Yes';
                $request->ResponseMessage   = $message;
                $request->write();

                $status = AthleteRequestStatusDetail::create();
                $status->OldStatus = $old_status;
                $status->NewStatus = 'ReportAbuse';
                $status->AthleteRequestID = $request->ID;
                $status->write();
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