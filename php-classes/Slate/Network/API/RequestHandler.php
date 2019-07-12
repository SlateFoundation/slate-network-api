<?php

namespace Slate\Network\API;

use \Emergence\People\PeopleRequestHandler;
use \Emergence\People\Person;

class RequestHandler extends \RequestHandler
{
    public static $apiKey;

    public static function handleRequest()
    {
        $providedApiKey = $_REQUEST['apiKey'];
        if (!static::$apiKey || !$providedApiKey || $providedApiKey != static::$apiKey) {
            return static::throwInvalidRequestError('Request Failed. apiKey parameter must be configured properly.');
        }

        switch (static::shiftPath()) {
            case 'users':
                return static::handleUsersRequest();

            default:
                return static::throwInvalidRequestError();
        }
    }

    public static function handleUsersRequest()
    {
        PeopleRequestHandler::$accountLevelBrowse = false;
        Person::$dynamicFields['PrimaryEmail']['accountLevelEnumerate'] = null;
        return PeopleRequestHandler::handleRequest();
    }
}