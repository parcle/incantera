<?php

use Lame\Lame;
use Lame\Settings;

/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 19/05/2017
 * Time: 2:02 PM
 */
class MyProfileAPI extends Controller {

    private static $allowed_actions = [
        'postSetMyRole',
        'postMyProfile',
        'postUpdateProfile',
        'postMyPictures',
        'postUploadMyPictures',
        'postDeleteMyPictures'
    ];

    public function Link($action = null) {
        return Controller::join_links("profileapi", "v1", $action);
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
            $response['error_messages'] = 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    private function checkMemberRole($token, $response) {
        try {
            //Get the member details related to passed token
            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();

            //Check the token is expired or not.
            if($member->MemberType == 'None' ) {
                $response['error_reason']   = 'NotSetRole';
                $response['error_messages'] = 'You have first to choose your role. Then try again.';
                return $response;
            }

            //token is valid so update the token time.
            $member->refreshRestToken();

            return $response;
        } catch (Exception $e) {
            $response['error_reason']   = 'UnknownError';
            $response['error_messages'] = 'Oops!! Some unknown error occurred during process. Please try again, if error consist then please contact our Administrator.';
            return $response;
        }
    }

    public function postSetMyRole(SS_HTTPRequest $req) {
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

            if($role!='Manager' && $role!='Attorney' && $role!='Trainer' && $role!='Athlete' && $role!='General') {
                $role = 'General';
            }

            //Get Member record from passed token value.
            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();

            $member->MemberType = $role;
            $member->write();

            //Create respected entries in table if not exists per role.
            if($role=='Manager') {
                //First we will check is record exists in manager table for current member id.
                if(!$manager = SportManager::get()->filter("MemberID", $member->ID )->first()) {
                    //If not exists then create new manager record.
                    $manager                = SportManager::create();
                    $manager->MemberID      = $member->ID;
                    $manager->Status        = 'Waiting';
                    $manager->write();
                }
            } else if($role=='Attorney') {
                //First we will check if record exists in Attorney table for current member id.
                if(!$attorney = SportAttorney::get()->filter("MemberID", $member->ID )->first()) {
                    //If not exists then create new Attorney Record.
                    $attorney                = SportAttorney::create();
                    $attorney->MemberID      = $member->ID;
                    $attorney->Status        = 'Waiting';
                    $attorney->write();
                }
            } else if($role=='Trainer') {
                //First we will check if record exists in Trainer table for current member id.
                if(!$trainer = Trainer::get()->filter("MemberID", $member->ID )->first()) {
                    //If not exists then create new Trainer record.
                    $trainer                = Trainer::create();
                    $trainer->MemberID      = $member->ID;
                    $trainer->Status        = 'Waiting';
                    $trainer->write();
                }
            } else if($role=='Athlete') {
                //First we will check if record exists in Athlete table for current member id.
                if(!$athlete = Athlete::get()->filter("MemberID", $member->ID )->first()) {
                    //If not exists then create new Athlete record.
                    $athlete                = Athlete::create();
                    $athlete->MemberID      = $member->ID;
                    $athlete->Status        = 'Waiting';
                    $athlete->write();
                }
            } else if($role=='General') {
                //First we will check if record exists in General User table for current member id.
                if(!$general = GeneralUser::get()->filter("MemberID", $member->ID )->first()) {
                    //If not exists then create new General User record.
                    $general                = GeneralUser::create();
                    $general->MemberID      = $member->ID;
                    $general->Status        = 'Approved';
                    $general->write();
                }
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

    public function postMyProfile(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token         = $req->requestVar('_token');

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

            $response = $this->checkMemberRole($token, $response);
            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Get Member record from passed token value.
            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();

            $response['response_data']['first_name']    = $member->FirstName;
            $response['response_data']['middle_name']   = $member->MiddleName;
            $response['response_data']['last_name']     = $member->Surname;
            $response['response_data']['email']         = $member->Email;

            //Set process_status = true and returns success response.
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

    public function postUpdateProfile(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $first_name     = $req->requestVar('first_name');
            $middle_name    = $req->requestVar('middle_name');
            $last_name      = $req->requestVar('last_name');
            $email          = $req->requestVar('email');

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

            //Set default variables with default values
            $error_flg  = true;
            $messages   = [];

            //Check validation of passed data
            if($first_name=='') {
                $error_flg = false;
                $messages['first_name'] = 'First name is required field.';
            }
            if($last_name=='') {
                $error_flg = false;
                $messages['last_name'] = 'Last name is required field.';
            }
            if($email=='') {
                $error_flg = false;
                $messages['email_name'] = 'Email is required field.';
            } else if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\./', $email)) {
                $error_flg = false;
                $messages['email_name'] = 'Email id is not in valid format. Please change it.';
            } else if($member1 = Member::get()->filter("Email", $email)->exclude('ID', $member->ID)->first()) {
                $error_flg = false;
                $messages['email_name'] = 'This email is already exists, please change.';
            }

            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_message']  = $messages;
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            $member->FirstName      = $first_name;
            $member->Surname        = $last_name;
            $member->MiddleName     = $middle_name;
            $member->Email          = $email;

            $member->write();
            $member->login();

            //Set process_status = true and returns success response.
            $response['process_status'] = true;
            $response['response_data']['token'] = $token;
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

    public function postMyPictures(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token         = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

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

    public function postUploadMyPictures(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token         = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

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

    public function postDeleteMyPictures(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token         = $req->requestVar('_token');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //First check the validation of passed data
            $response = $this->checkDataValidation($req, $response);

            //If error_reason is not blank then returns json format with validation messages.
            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

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