<?php

namespace Slate\Network\Site;

use Site;

use Emergence\Connectors\AbstractConnector;
use Emergence\People\PeopleRequestHandler;
use Emergence\People\Groups\GroupsRequestHandler;
use Emergence\People\Person;

use Slate\People\Student;

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

            case 'groups':
                return static::handleGroupsRequest();

            default:
                return parent::handleRequest();
        }
    }

    public static function handleFinishNetworkLoginRequest()
    {
        $queryParameters = http_build_query([
            'hub_token' => JWT::encode([
                'user' => array_merge(
                    $GLOBALS['Session']->Person->getSummary(),
                    [
                        'PrimaryEmail' => $GLOBALS['Session']->Person->PrimaryEmail->toString()
                    ]
                ),
                'hostname' => Site::getConfig('primary_hostname')
            ], static::$apiKey)
        ]);

        if (!$_REQUEST['redirectUrl']) {
            return static::throwInvalidRequestError('Redirect URL not set. Please try again or contact an administrator.');
        }

        $queryParameterGlue = strpos($_REQUEST['redirectUrl'], '?') === -1 ? '?' : '&';
        // TODO: can we always assume query params are being appending to existing, or should we check?
        header('Location: ' . $_REQUEST['redirectUrl'] . "{$queryParameterGlue}{$queryParameters}");
    }

    public static function handleNetworkLoginRequest()
    {
        $queryParameters = http_build_query([
            'returnUrl' => '/network-api/finish-login?' . http_build_query(['redirectUrl' => $_REQUEST['redirectUrl']]),
            '_LOGIN[username]' => $_REQUEST['username']
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
        Student::$fields['StudentNumber']['accountLevelEnumerate'] = null;

        return PeopleRequestHandler::handleRequest();
    }

    public static function handleGroupsRequest()
    {
        $providedApiKey = $_REQUEST['apiKey'];
        if (!static::$apiKey || !$providedApiKey || $providedApiKey != static::$apiKey) {
            return static::throwInvalidRequestError('Request Failed. apiKey parameter must be configured properly.');
        }

        GroupsRequestHandler::$accountLevelBrowse = false;


        return GroupsRequestHandler::handleRequest();
    }
}
