<?php
// Protection contre les inclusions multiples
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);



// Paramètres de connexion
class DataBase
{
    private static $pdo = null;
    private static $host = 'localhost'; 
    private static $db   = 'check_master_db';
    private static $user = 'root';
    private static $pass = ''; // mot de passe défini dans docker-compose.yml
    private static $charset = 'utf8';

    public static function getConnection()
    {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
                self::$pdo = new PDO($dsn, self::$user, self::$pass);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
                
                // Configuration pour optimiser les connexions
                self::$pdo->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
                self::$pdo->setAttribute(PDO::ATTR_PERSISTENT, false);
                
            } catch (PDOException $e) {
                error_log("Erreur de connexion à la base de données: " . $e->getMessage());
                die("Erreur de connexion : " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    public static function close()
    {
        if (self::$pdo !== null) {
            self::$pdo = null;
        }
    }

    public static function isConnected()
    {
        return self::$pdo !== null;
    }

    public static function reconnect()
    {
        self::close();
        return self::getConnection();
    }

    public static function getConnectionInfo()
    {
        return [
            'host' => self::$host,
            'database' => self::$db,
            'user' => self::$user,
            'charset' => self::$charset,
            'connected' => self::isConnected()
        ];
    }
}
