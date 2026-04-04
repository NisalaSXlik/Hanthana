<?php
require_once __DIR__ . "/../core/Database.php";

class BaseModel
{
    protected static ?PDO $db = null;
    protected PDO $dbInstance; // shared db instance across models

    public function __construct()
    {
        if (self::$db === null)
        {
            $database = new Database;
            self::$db = $database->getConnection();
        }
        $this->dbInstance = self::$db;
    }
}