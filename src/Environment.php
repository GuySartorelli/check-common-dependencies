<?php

namespace CheckCommonModules;

use M1\Env\Parser;
use Symfony\Component\Filesystem\Path;

final class Environment
{
    private static $parser;

    private static function getParser()
    {
        if (!self::$parser) {
            $envPath = Path::makeAbsolute(Path::canonicalize('.env'), getcwd());
            self::$parser = new Parser(file_get_contents($envPath));
        }
        return self::$parser;
    }

    public static function get(string $var)
    {
        return self::getParser()->getContent($var);
    }
}