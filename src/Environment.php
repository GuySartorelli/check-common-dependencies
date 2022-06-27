<?php

namespace CheckCommonModules;

use M1\Env\Parser;

final class Environment
{
    private static $parser;

    private static function getParser()
    {
        if (!self::$parser) {
            self::$parser = new Parser(file_get_contents('test_context.env'));
        }
        return self::$parser;
    }

    public static function get(string $var)
    {
        return self::getParser()->getContent($var);
    }
}