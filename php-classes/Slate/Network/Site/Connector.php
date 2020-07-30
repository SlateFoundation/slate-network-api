<?php

namespace Slate\Network\Site;

use Site;

use Emergence\Connectors\AbstractConnector;
use \Emergence\People\PeopleRequestHandler;
use \Emergence\People\Person;

use Firebase\JWT\JWT;

class Connector extends AbstractConnector
{
    // Connector cfg
    public static $title = 'Network Site Connector';
    public static $connectorId = 'slate-network-site';

    // Slate Network cfg
    public static $hubUrl;
    public static $apiKey;

    // RequestHandler cfg
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
                return parent::handleRequest();
        }
    }

    public static function launchWithAuth()
    {
        $handlerPath = array_merge(['site-root', 'network'], array_filter(Site::$pathStack));
        $handlerPath[count($handlerPath) - 1] .= '.php';

        $node = Site::resolvePath($handlerPath);

        if (!$node) {
            return static::throwInvalidRequestError('test123');
        }

        $GLOBALS['_NETWORK'] = [
            'site' => Site::getConfig('handle'),
            'user' => [
                'username' => 'nbey@b-21.org',
                'email' => 'nbey@b-21.org',
                'student_number' => 12345,
                'first_name' => 'NafisTest',
                'last_name' => 'BeyTest'
            ]
        ];

        Site::executeScript($node);
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