<?php
    require_once 'vendor/autoload.php';

    /**
     * testing date function definitions
     */
    function getCurrentDateTimeInMySQL()
    {
        return DateTesting::$dateTime ?: \date("Y-m-d H:i:s");
    }

    function getCurrentDateTimeMinusDaysInMySQL($days)
    {
        if (DateTesting::$dateTime == null) {
            return date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), (date('d')-$days), date('Y')));
        }

        $date = new DateTime(DateTesting::$dateTime);
        $date->modify('-' . $days . ' day');
        return $date->format('Y-m-d H:i:s');
    }

    function getCurrentDateTimePlusDaysInMySQL($days)
    {
        if (DateTesting::$dateTime == null) {
            return date('Y-m-d H:i:s', mktime(date('H'), (date('i')), date('s'), date('m'), date('d')+$days, date('Y')));;
        }

        $date = new DateTime(DateTesting::$dateTime);
        $date->modify('+' . $days . ' day');
        return $date->format('Y-m-d H:i:s');
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

    global $c_send_email;
    $c_send_email = false;

    global $environment;
    $environment = new \cs_environment();
