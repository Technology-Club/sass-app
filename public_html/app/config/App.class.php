<?php

/**
 * Created by PhpStorm.
 * User: rdok
 * Date: 10/12/2014
 * Time: 7:49 PM
 */
class App
{
    const APPOINTMENT_BTN_URL = 'appointmentBtn';
    const REPORT_BTN_URL = 'reportBtn';
    private static $settings;

    /**
     * Check if current time is during working hours
     * @return bool
     */
    static function isWorkingDateTimeOn()
    {
        date_default_timezone_set(App::getTimeZone());

        $curWorkingDate = new DateTime();

        $curWorkingHour = intval($curWorkingDate->format('H'));
        $curWorkingDay = intval($curWorkingDate->format('N'));

        // save resources - only run cron at working hours/day (monday - friday)
        if ($curWorkingHour < self::getFirstWorkingHour() || $curWorkingHour > self::getLastWorkingHour() ||
            $curWorkingDay > self::getLastWorkingDay()
        ) {
            return false;
        }

        return true;
    }

    public static function getTimeZone()
    {
        return self::$settings['TIMEZONE'];
    }

    public static function getFirstWorkingHour()
    {
        return self::$settings['FIRST_WORKING_HOUR'];
    }

    public static function getLastWorkingHour()
    {
        return self::$settings['LAST_WORKING_HOUR'];
    }

    public static function getLastWorkingDay()
    {
        return self::$settings['LAST_WORKING_DAY'];
    }

    /**
     * Force Date default time zone
     * @return DateTime
     */
    static function getCurWorkingDate()
    {
        date_default_timezone_set(self::getTimeZone());

        $curWorkingDate = new DateTime();

        return $curWorkingDate;
    }

    /**
     * Check if App is accessed from ssl
     *
     * @return bool
     */
    public static function isSecure()
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            return true;
        }

        if ( ! empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ||
            ! empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Load App Settings (database, email & ReCaptcha credentials as well as working hours.)
     * @param $settingsFile
     */
    public static function loadSettings($settingsFile)
    {

        self::$settings = require $settingsFile;
    }

    /**
     * @return mixed
     */
    public static function getDbHost()
    {
        return self::$settings['DB_HOST'];
    }

    public static function getDbName()
    {
        return self::$settings['DB_NAME'];
    }

    public static function getDbUsername()
    {
        return self::$settings['DB_USERNAME'];
    }

    public static function getDbPassword()
    {
        return self::$settings['DB_PASSWORD'];
    }

    public static function getDbPort()
    {
        return self::$settings['DB_PORT'];
    }

    public static function getMailgunKey()
    {
        return self::$settings['MAILGUN_KEY'];
    }

    public static function getMailgunDomain()
    {
        return self::$settings['MAILGUN_DOMAIN'];
    }

    public static function getReCaptchaSiteKey()
    {
        return self::$settings['RECAPTCHA_SITE_KEY'];
    }

    public static function getReCaptchaSecretKey()
    {
        return self::$settings['RECAPTCHA_SECRET_KEY'];
    }

    public static function getName()
    {
        return self::$settings['NAME'];
    }

    public static function getVersion()
    {
        return self::$settings['VERSION'];
    }

    public static function getPDOErrorMode()
    {
        return self::$settings['PDO_ERROR_MODE'];
    }

    public static function githubIssue($number)
    {
        if(!empty($number)){
            return "https://github.com/sass-team/sass-app/issues/$number";
        }
        return self::$settings['GITHUB_NEW_ISSUE_URL'];
    }

    /**
     * Write the contents to the file, using the FILE_APPEND flag to append the content to the end of the file
     * and the LOCK_EX flag to prevent anyone else writing to the file at the same time
     * @param $messsage
     * @internal param $e
     */
    public static function storeError($messsage)
    {
        $file = ROOT_PATH . '../../app_errors.log';

        $messsage = App::getCurrentTime() . $messsage;

        file_put_contents($file, $messsage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Format a current time to be used sql calculations.
     * @return string
     */
    public static function getCurrentTime()
    {
        date_default_timezone_set(self::getTimeZone());

        $now = new DateTime();

        return $now->format(Dates::DATE_FORMAT_IN);
    }

    public static function getDefaultDateFormat()
    {
        return self::$settings['DEFAULT_DATE_FORMAT'];
    }

    public static function getAppointmentsListUrl()
    {
        return self::getHostname() . '/appointments/list';
    }

    public static function getHostname()
    {
        if (App::env('testing')) return 'sass.app';

        $hosts = self::getHostNames();

        $hostsWithSSL = $hosts['SSL'];

        $hostname = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $hostsWithSSL['production'];

        return $hostname;
    }

    public static function env($string)
    {
        return getenv('env') == $string;
    }

    public static function getHostNames()
    {
        return self::$settings['HOST_NAMES'];
    }

    public static function mailFrom()
    {
        return "noreply@" . App::getDomainName();
    }

    /**
     * Format App url to ssl
     *
     * @return string
     */
    public static function getDomainName()
    {
        if (self::isHostnameInSSLList()) {
            return "https://" . $_SERVER['SERVER_NAME'];
        }

        return "http://" . $_SERVER['SERVER_NAME'];
    }

    /**
     * @return array
     */
    public static function isHostnameInSSLList()
    {
        $domainName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : "cron";

        $hosts = self::getHostNames();

        $hostsWithSSL = $hosts['SSL'];

        return in_array($domainName, $hostsWithSSL);
    }

    public static function getReportsListUrl()
    {
        return self::getHostname() . '/appointments/list';
    }

    public static function getenv($env)
    {
        return self::$settings[$env];
    }
}
