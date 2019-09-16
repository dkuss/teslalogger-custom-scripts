<?php


namespace Logfile2Telegram\Service;


class DbConnection
{
    private static $dbLink;

    private static function connectDb()
    {
        $config = require __DIR__ . '/../../config/config.php';
        static::$dbLink = mysqli_connect($config['db_host'], $config['db_user'], $config['db_password']) or die ("Verbindungsversuch fehlgeschlagen: " . mysql_error());
        mysqli_select_db(static::$dbLink, $config['db_name']) or die("Konnte die Datenbank nicht waehlen.");
        mysqli_set_charset(static::$dbLink, 'utf8') or die("Konnte die Datenbank nicht auf UTF-8 setzen.");
    }

    public static function getDbLink()
    {
        if (static::$dbLink === null) {
            static::connectDb();
        }
        return static::$dbLink;
    }
}
