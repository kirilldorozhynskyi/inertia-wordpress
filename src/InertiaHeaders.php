<?php

namespace BoxyBird\Inertia;

class InertiaHeaders
{
    protected static $headers;

    public static function all()
    {
        if (self::$headers === null) {
            self::$headers = array_change_key_case(getallheaders(), CASE_LOWER);
        }

        return self::$headers;
    }

    public static function inRequest()
    {
        $headers = self::all();

        if (isset($headers['x-requested-with'])
            && $headers['x-requested-with'] === 'XMLHttpRequest'
            && isset($headers['x-inertia'])
            && $headers['x-inertia'] === 'true'
        ) {
            return true;
        }

        return false;
    }

    public static function addToResponse()
    {
        header('Vary: Accept');
        header('X-Inertia: true');
    }
}
