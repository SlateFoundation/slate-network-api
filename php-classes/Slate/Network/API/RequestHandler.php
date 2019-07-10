<?php

namespace Slate\Network\API;

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
        return \Emergence\People\PeopleRequestHandler::handleRequest();
    }
}