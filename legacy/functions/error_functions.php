<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

// user defined error handling function. This one is called for all
// non-fatal errors, unfortunately.
function commSyErrorHandler($errno, $errstr, $errfile, $errline)
{
    global $environment;

    // check if we want to report this error
    if ($errno and error_reporting()) {
        // timestamp for the error entry
        $dt = date('Y-m-d H:i:s');

        // define an assoc array of error string
        $errortype = [1 => 'Error', 2 => 'Warning', 4 => 'Parsing Error', 8 => 'Notice', 16 => 'Core Error', 32 => 'Core Warning', 64 => 'Compile Error', 128 => 'Compile Warning', 256 => 'User Error', 512 => 'User Warning', 1024 => 'User Notice', 2048 => 'Notice'];

        // find out current environ, if any
        if (isset($environment)) {
            $current_user = $environment->getCurrentUserItem();
            $user = '';
            if (isset($current_user)) {
                $user = $current_user->getFullName();
            }
            if (empty($user)) {
                $user = 'unknown';
            }
        } else {
            $user = 'unknown';
        }

        $context = -1;
        $module = 'unknown';
        $function = 'unknown';
        if (isset($environment)) {
            $context = $environment->getCurrentContextID();
            if (empty($context)) {
                $context = -1;
            }
            $module = $environment->getCurrentModule();
            if (empty($module)) {
                $module = 'unknown';
            }
            $function = $environment->getCurrentFunction();
            if (empty($function)) {
                $function = 'unknown';
            }
        }

        $referer = '';
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        }

        if (ini_get('display_errors')) {
            @header('HTTP/1.1 400 Bad Request');

            $err = '<br /><CENTER><TABLE BORDER="1" CELLSPACING="0" CELLPADDING="2" WIDTH="70%" summary="Layout">'."\n";
            $err .= "\t".'<TR><TD COLSPAN="2"><B>PHP Error</B></TD></TR>'."\n";
            $err .= "\t".'<TR><TD>Type: </TD><TD>'.$errortype[$errno].'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>Message: </TD><TD>'.$errstr.'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>File: </TD><TD>'.$errfile.'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>Line: </TD><TD>'.$errline.'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>Context: </TD><TD>'.$context.'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>Module: </TD><TD>'.$module.'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>Function: </TD><TD>'.$function.'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>User: </TD><TD>'.$user.'</TD></TR>'."\n";
            $err .= "\t".'<TR><TD>Time: </TD><TD>'.getCurrentDateTimeInMySQL().'</TD></TR>'."\n";
            $err .= '</TABLE></CENTER><br />'."\n";
            echo $err;
        }

        if (ini_get('log_errors')) {
            $err = 'PHP '.$errortype[$errno].':  '.$errstr.' in '.$errfile.' on line '.$errline;
            $err .= ' (context='.$context.', module='.$module.', function='.$function.', user='.$user.')';
            error_log($err, 0);
        }

        // if error is fatal, stop script execution
        $fatal_errors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (in_array($errno, $fatal_errors) and ini_get('display_errors')) {
            $exitmsg = 'Script execution stopped due to a fatal CommSy error. ';
            exit($exitmsg);
        }
    }

    return true;
}
set_error_handler('commSyErrorHandler');
