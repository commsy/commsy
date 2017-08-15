<?php
require_once 'vendor/autoload.php';

/**
 * testing date function definitions
 */
function getCurrentDateTimeInMySQL()
{
    return DateTesting::$dateTime ?: \date("Y-m-d H:i:s");
}

function getCurrentDateTimeMinusDaysInMySQL($days, $withoutTime = false)
{
    if (DateTesting::$dateTime == null) {
        if (!$withoutTime) {
            return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), (date('d') - $days), date('Y')));
        } else {
            return date('Y-m-d 00:00:00', mktime(date('H'), date('i'), date('s'), date('m'), (date('d') - $days), date('Y')));
        }
    }

    $date = new DateTime(DateTesting::$dateTime);
    $date->modify('-' . $days . ' day');

    if (!$withoutTime) {
        return $date->format('Y-m-d H:i:s');
    } else {
        return $date->format('Y-m-d 00:00:00');
    }
}

function getCurrentDateTimePlusDaysInMySQL($days, $withoutTime = false)
{
    if (DateTesting::$dateTime == null) {
        if (!$withoutTime) {
            return date('Y-m-d H:i:s', mktime(date('H'), (date('i')), date('s'), date('m'), date('d') + $days, date('Y')));
        } else {
            return date('Y-m-d 00:00:00', mktime(date('H'), (date('i')), date('s'), date('m'), date('d') + $days, date('Y')));
        }
    }

    $date = new DateTime(DateTesting::$dateTime);
    $date->modify('+' . $days . ' day');

    if (!$withoutTime) {
        return $date->format('Y-m-d H:i:s');
    } else {
        return $date->format('Y-m-d 00:00:00');
    }
}

class DateTesting
{
    public static $dateTime;
}

require_once 'etc/cs_constants.php';
require_once 'etc/cs_config.php';
require_once 'functions/misc_functions.php';
require_once 'functions/date_functions.php';

require_once 'classes/cs_environment.php';
require_once 'classes/interfaces/cs_export_import_interface.php';

global $c_send_email;
$c_send_email = true;

global $environment;
$environment = new \cs_environment();