<?php

use Lame\Lame;
use Lame\Settings;


/**
 * Created by PhpStorm.
 * User: MEHUL
 * Date: 19/05/2017
 * Time: 11:02 AM
 */
class AuthenticationAPI extends Controller {

    private static $allowed_actions = [
        'postRegistration',
        'postCheckLogin',
        'postForgotPassword',
        'postUpdatePassword',
        'postLogout'
    ];

    public function Link($action = null) {
        return Controller::join_links("authapi", "v1", $action);
    }

    /**
     * @name        checkDataValidation
     * @param       $req
     * @param       $response
     * @return      mixed
     * @description This function is used to check validation of Registration parameters.
     * @internal    $first_name
     * @internal    $last_name
     * @internal    $email
     * @internal    $password
     */
    private function checkDataValidation( $req, $response ) {
        try {

            //First get all passed parameters in different related variables.
            $first_name         = $req->requestVar('first_name');
            $last_name          = $req->requestVar('last_name');
            $email              = $req->requestVar('email');
            $password           = $req->requestVar('password');

            //Define default variable with values.
            $error_flg  = true;
            $messages   = [];

            //Check validation of all paramters
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
            } else if($member = Member::get()->filter("Email", Convert::raw2sql($email))->first()) {
                $error_flg = false;
                $messages['email_name'] = 'This email is already exists, please change.';
            }
            if($password=='') {
                $error_flg = false;
                $messages['password']   = 'Password is required field.';
            } else if(strlen($password) < 8) {
                $error_flg = false;
                $messages['password']   = 'Password length must be greater than or equal to 8.';
            }

            //If error_flg is false then any of parameter is not valid then returns error messages with reason.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_message']  = $messages;
            }

            //Returns success response without error reason.
            return $response;
        } catch (Exception $e) {
            //Define default variables and related json values in json format.
            $response['error_reason']   = 'UnknownError';
            $response['error_message']  = 'Oops!! Some unknown error occurred during validation process. Please try again, if error consist then please contact our Administrator.';

            return $response;
        }
    }

    /**
     * @name        checkToken
     * @param       $token
     * @param       $response
     * @return      mixed
     * @description This function is used to check token validation.
     */
    private function checkToken($token, $response){
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
    }

    /**
     * @name        postRegistration
     * @param       SS_HTTPRequest $req
     * @return      SS_HTTPResponse|void
     * @description This function is used to store registration user details in database.
     * @internal    $first_name
     * @internal    $last_name
     * @internal    $email
     * @internal    $password
     */
    public function postRegistration(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $first_name         = $req->requestVar('first_name');
            $last_name          = $req->requestVar('last_name');
            $email              = $req->requestVar('email');
            $password           = $req->requestVar('password');

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

            //create new member from Member Class.
            $member = Member::create();
            $member->FirstName  = $first_name;
            $member->Surname    = $last_name;
            $member->Email      = $email;
            $member->Password   = $password;

            //store the data in database and then login from that user and make token
            $member->write();
            $member->login();
            $member->createRestToken();

            //set create_user_status to true, new_user_login status to true and token in json array.
            $response['process_status'] = true;
            $response['response_data']['token'] = $member->RESTToken;

            //complete process by returns the json array with all details.
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

    /**
     * @name        postCheckLogin
     * @param       SS_HTTPRequest $req
     * @return      SS_HTTPResponse|void
     * @description This function is used to check Login for passed username and password.
     * @internal    $email
     * @internal    $password
     */
    public function postCheckLogin(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $email              = $req->requestVar('email');
            $password           = $req->requestVar('password');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //Define default varaibles and set default values.
            $error_flg  = true;
            $messages   = [];

            //check validation of passed parameters
            if($email=='') {
                $error_flg = false;
                $messages['email_name'] = 'Email is required field.';
            } else if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\./', $email)) {
                $error_flg = false;
                $messages['email_name'] = 'Email id is not in valid format. Please change it.';
            }
            if($password=='') {
                $error_flg = false;
                $messages['password']   = 'Password is required field.';
            }

            //If error_flg == false then any of parameter is not valid then this function will returns invalid data error.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages']  = $messages;
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Get Member by email id.
            if($member = Member::get()->filter("Email", Convert::raw2sql($email))->first()) {
                //check the password with selected member
                if ($member->checkPassword($password)->valid()) {
                    //Set default process_status = true.
                    $response['process_status'] = true;

                    //Create token and set current member as login
                    $member->createRestToken();
                    $member->login();

                    //Store Token in response array
                    $response['response_data']['token'] = $member->RESTToken;
                } else {
                    //Password is not match
                    $response['error_reason']   = 'InvalidPassword';
                    $response['error_messages']  = 'Oops!! Your password is not match with our of records. Please try again.';
                    return new SS_HTTPResponse(Convert::array2json($response), 200);
                }
            } else {
                //Email is not match
                $response['error_reason']   = 'InvalidUserName';
                $response['error_messages']  = 'Oops!! Your username is not match with any of records. Please try again.';
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //complete process by returns the json array with all details.
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

    /**
     * @name        postForgotPassword
     * @param       SS_HTTPRequest $req
     * @return      SS_HTTPResponse|void
     * @description This function is used to reset password for forgot password feature.
     * @internal    $email
     */
    public function postForgotPassword(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $email              = $req->requestVar('email');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            //Set default variables and default values.
            $error_flg  = true;
            $messages   = [];

            //check validation of passed parameters
            if($email=='') {
                $error_flg = false;
                $messages['email_name'] = 'Email is required field.';
            } else if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@.+\./', $email)) {
                $error_flg = false;
                $messages['email_name'] = 'Email id is not in valid format. Please change it.';
            } else if(!$member = Member::get()->filter("Email", Convert::raw2sql($email))->first()) {
                $error_flg = false;
                $messages['email_name'] = 'Email id is not exists in our database. Please try again.';
            }

            //if error_flg == false then parameter is not valid.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages']  = $messages;
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //create new password from random string.
            $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789._-";
            $pass = array(); //remember to declare $pass as an array
            $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
            for ($i = 0; $i < 8; $i++) {
                $n = rand(0, $alphaLength);
                $pass[] = $alphabet[$n];
            }
            $newpass = implode($pass);

            //Find the user from the database and update new password in database.
            if($member = Member::get()->filter("Email", Convert::raw2sql($email))->first()) {
                $member->changePassword($newpass);
                $response['response_data']['newpass'] = $newpass;
                $member->login();

                //If the password is change successfully then set response_status to true.
                $response['process_status'] = true;
                $member->createRestToken();
            }

            //send mail to user with new password.
            $from = 'noreply@incantera.nl';
            $body = 'Hello '.$member->FirstName." ".$member->LastName.'<br><br>
                    Your Password is changed successfully. Please check bellow is your new password:<br>
                    Password : '.$newpass.'<br>
                    If you have any query then please contact us.
                ';
            $send_mail = new Email($from, $email, 'Forgot Password', $body);
            $result = $send_mail->send();

            //complete process by returns the json array with all details.
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

    /**
     * @name        postUpdatePassword
     * @param       SS_HTTPRequest $req
     * @return      SS_HTTPResponse|void
     * @description This function is used to update password for logged in user.
     * @internal    $_token
     * @internal    $old_password
     * @internal    $new_password
     * @internal    $con_password
     */
    public function postUpdatePassword(SS_HTTPRequest $req) {
        //If the request is not post then returns 400 error
        if(!$req->isPOST()) return $this->httpError(400);

        try {

            //First get all passed parameters in different related variables.
            $token          = $req->requestVar('_token');
            $old_password   = $req->requestVar('old_password');
            $new_password   = $req->requestVar('new_password');
            $con_password   = $req->requestVar('con_password');

            //Define default variables and related json values in json format.
            $response = [   'process_status'    => false,
                'error_reason'          => '',
                'error_messages'        => '',
                'response_data'         => [] ];

            $response = $this->checkToken($token, $response);

            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Set default variables with default values.
            $error_flg  = true;
            $messages   = [];

            //Check validation of passed parameters.
            if(!$old_password) {
                $error_flg = false;
                $messages['old_password'] = 'Old Password is required field.';
            } if($member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first()) {
                if(!$member->checkPassword($old_password)->valid()) {
                    $error_flg = false;
                    $messages['old_password'] = 'Old Password is not valid. Please try again!';
                }
            }

            if(!$new_password || $new_password=='') {
                $error_flg = false;
                $messages['new_password'] = 'New Password is required field.';
            } else if(strlen($new_password) < 8) {
                $error_flg = false;
                $messages['new_password'] = 'Password needs atleast 8 characters long.';
            }

            if($new_password != $con_password) {
                $messages['$con_password'] = 'Password & Confirm Password are not match!!';
            }

            //If error_flg == false then any of passed parameters is not valid then this function will returns error message.
            if($error_flg===false) {
                $response['error_reason']   = 'InvalidData';
                $response['error_messages']  = $messages;
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //find member from token
            if($member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first()) {
                //check password of related member
                if($member->checkPassword($old_password)->valid()) {
                    //Update Password & refresh token
                    $member->changePassword($new_password);
                    $member->login();
                    $member->refreshRestToken();
                    $response['response_data']['token'] = $member->RESTToken;
                }
            }

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

    /**
     * @name        postLogout
     * @param       SS_HTTPRequest $req
     * @return      SS_HTTPResponse|void
     * @description This function is used to get Logout from logged in account.
     * @internal    $_token
     */
    public function postLogout(SS_HTTPRequest $req) {
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

            $response = $this->checkToken($token, $response);

            if($response['error_reason'] != '') {
                return new SS_HTTPResponse(Convert::array2json($response), 200);
            }

            //Get member from passed token.
            $member = Member::get()->filter("RESTToken", Convert::raw2sql($token))->first();

            //Get member logged out.
            $member->logout();

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

}