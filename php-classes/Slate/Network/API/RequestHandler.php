<?php

namespace Slate\Network\API;

use \Emergence\People\PeopleRequestHandler;
use \Emergence\People\Person;

use Firebase\JWT\JWT;

class RequestHandler extends \RequestHandler
{
    public static $apiKey = 'abc';
    public static $responseMode = 'json';
    public static $userResponseModes = [
        'application/json' => 'json'
    ];

    public static function handleRequest($action = null)
    {
        $action = $action ?: static::shiftPath();

        switch ($action) {
            case 'users':
                return static::handleUsersRequest();

            case 'login':
                return static::handleNetworkLoginRequest();

            case 'finish-login':
                return static::handleFinishNetworkLoginRequest();

            default:
                return static::throwInvalidRequestError();
        }
    }

    public static function handleFinishNetworkLoginRequest()
    {
        session_start();
        // redirect to domain + returnUrl decoded from token
        if (!$token = $_SESSION['JWT']) {
            return static::throwInvalidRequestError('Unable to decode JWT Token. Please contact an administrator, or try again.');
        }

        $redirectTo = 'http://' . $token->domain . $token->returnUrl;
        $queryParameters = http_build_query([
            'domain' => \Site::getConfig('primary_hostname'),
            'JWT' => JWT::encode([
                'session_handle' => $GLOBALS['Session']->Handle,
                // 'person' => $_SESSION['User']
            ], static::$apiKey)
        ]);

        header('Location: '. $redirectTo. "?{$queryParameters}");
    }

    public static function handleNetworkLoginRequest()
    {
        session_start();
        // decode JWT token from slate networkhub site
        try {
            $token = JWT::decode($_REQUEST['JWT'], static::$apiKey, ['HS256']);
            $_SESSION['JWT'] = $token;
            \MICS::dump($_SESSION, 'session b4');
            sleep(5);
        } catch (\Exception $e) {
            return static::throwInvalidRequestError('Unable to decode JWT Token. Please contact an administrator, or try again.');
        }

        $queryParameters = http_build_query([
            'username' => $token->username,
            'returnUrl' => '/network-api/finish-login'
        ]);

        \Site::redirect('/login?'.$queryParameters);
    }

    public static function handleUsersRequest()
    {
        $providedApiKey = $_REQUEST['apiKey'];
        if (!static::$apiKey || !$providedApiKey || $providedApiKey != static::$apiKey) {
            return static::throwInvalidRequestError('Request Failed. apiKey parameter must be configured properly.');
        }

        PeopleRequestHandler::$accountLevelBrowse = false;
        Person::$dynamicFields['PrimaryEmail']['accountLevelEnumerate'] = null;
        return PeopleRequestHandler::handleRequest();
    }
}