<?php

namespace App\Helpers;

class BashCommand 
{
    public static function run($input, $binary, $flags, $ignoreOutput = false)
    {
        return shell_exec('echo "' . $input . '" | ' . $binary . ' ' . $flags);
    }
}
