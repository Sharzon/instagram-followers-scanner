<?php

namespace App;

use PDO;

class DB
{
    const HOST = 'localhost';
    const PORT = '3306';
    const DBNAME = 'instagram_followers_scanner';
    const CHARSET = 'utf8';
    const USERNAME = 'root';
    const PASSWORD = 'manman';
    const OPTIONS = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    static private $pdo;

    private function __construct() { }

    static public function init()
    {
        $pdo = self::getPDO(false);

        $pdo->exec(
            'CREATE DATABASE '.self::DBNAME.';
            USE '.self::DBNAME.';
            CREATE TABLE followers (
                id VARCHAR(30),
                account VARCHAR(30) NOT NULL,
                date_in DATE NOT NULL,
                date_out DATE,
                active BOOLEAN,
                PRIMARY KEY (id, account)
            );
            CREATE TABLE log‐scan (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                datetime DATETIME NOT NULL,
                account VARCHAR(30) NOT NULL,
                followers INT(6)
            );
            CREATE TABLE posts (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                account VARCHAR(30) NOT NULL,
                post_id VARCHAR(30) UNIQUE NOT NULL, 
                actions INT(6)
            );
            CREATE TABLE action (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                datetime DATETIME NOT NULL,
                post_id INT(6) UNSIGNED NOT NULL,
                followers INT(6), 
                other_users INT(6),
                FOREIGN KEY (post_id) REFERENCES posts(id)
            );'
        );
    }

    static function getDSN($with_database = true)
    {
        $dsn = 'mysql:host='.self::HOST.';port='.self::PORT.';charset='.self::CHARSET;
        if ($with_database) {
            $dsn .= ';dbname='.self::DBNAME;
        }

        return $dsn;
    }

    static public function getPDO($with_database = true)
    {
        if (!self::$pdo) {
            self::$pdo = new PDO(
                self::getDSN($with_database),
                self::USERNAME,
                self::PASSWORD,
                self::OPTIONS
            );
        }

        return self::$pdo;
    }

    static public function pdoSetFromDict($dict) {
        $parts = [];

        foreach ($dict as $field => $value) {
            $parts[] = "`".str_replace("`","``",$field)."` = :$field";
        }

        return implode(", ", $parts);
    }

    static public function pdoAndSequence($array) {
        $parts = [];

        foreach ($array as $field) {
            $parts[] = "`".str_replace("`","``",$field)."` = :$field";
        }

        return implode(" AND ", $parts);
    }

    static public function pdoAndSequenceFromDict($dict) {
        return self::pdoAndSequence(array_keys($dict));
    }
}

