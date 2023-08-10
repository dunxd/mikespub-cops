<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

namespace SebLucas\Cops\Calibre;

use SebLucas\Cops\Input\Config;
use SebLucas\Cops\Language\Translation;
use Exception;
use PDO;

class Database
{
    private static $db = null;

    /**
     * Summary of isMultipleDatabaseEnabled
     * @return bool
     */
    public static function isMultipleDatabaseEnabled()
    {
        global $config;
        return is_array($config['calibre_directory']);
    }

    /**
     * Summary of useAbsolutePath
     * @param mixed $database
     * @return bool
     */
    public static function useAbsolutePath($database)
    {
        global $config;
        $path = self::getDbDirectory($database);
        return preg_match('/^\//', $path) || // Linux /
               preg_match('/^\w\:/', $path); // Windows X:
    }

    /**
     * Summary of noDatabaseSelected
     * @param mixed $database
     * @return bool
     */
    public static function noDatabaseSelected($database)
    {
        return self::isMultipleDatabaseEnabled() && is_null($database);
    }

    /**
     * Summary of getDbList
     * @return array
     */
    public static function getDbList()
    {
        global $config;
        if (self::isMultipleDatabaseEnabled()) {
            return $config['calibre_directory'];
        } else {
            return ["" => $config['calibre_directory']];
        }
    }

    /**
     * Summary of getDbNameList
     * @return array
     */
    public static function getDbNameList()
    {
        global $config;
        if (self::isMultipleDatabaseEnabled()) {
            return array_keys($config['calibre_directory']);
        } else {
            return [""];
        }
    }

    /**
     * Summary of getDbName
     * @param mixed $database
     * @return string
     */
    public static function getDbName($database)
    {
        global $config;
        if (self::isMultipleDatabaseEnabled()) {
            if (is_null($database)) {
                $database = 0;
            }
            if (!preg_match('/^\d+$/', $database)) {
                self::error($database);
            }
            $array = array_keys($config['calibre_directory']);
            return  $array[$database];
        }
        return "";
    }

    /**
     * Summary of getDbDirectory
     * @param mixed $database
     * @return string
     */
    public static function getDbDirectory($database)
    {
        global $config;
        if (self::isMultipleDatabaseEnabled()) {
            if (is_null($database)) {
                $database = 0;
            }
            if (!preg_match('/^\d+$/', $database)) {
                self::error($database);
            }
            $array = array_values($config['calibre_directory']);
            return  $array[$database];
        }
        return $config['calibre_directory'];
    }

    // -DC- Add image directory
    /**
     * Summary of getImgDirectory
     * @param mixed $database
     * @return string
     */
    public static function getImgDirectory($database)
    {
        global $config;
        if (self::isMultipleDatabaseEnabled()) {
            if (is_null($database)) {
                $database = 0;
            }
            $array = array_values($config['image_directory']);
            return  $array[$database];
        }
        return $config['image_directory'];
    }

    /**
     * Summary of getDbFileName
     * @param mixed $database
     * @return string
     */
    public static function getDbFileName($database)
    {
        return self::getDbDirectory($database) .'metadata.db';
    }

    /**
     * Summary of error
     * @param mixed $database
     * @throws \Exception
     * @return never
     */
    private static function error($database)
    {
        if (php_sapi_name() != "cli") {
            header("location: " . Config::ENDPOINT["check"] . "?err=1");
        }
        throw new Exception("Database <{$database}> not found.");
    }

    /**
     * Summary of getDb
     * @param mixed $database
     * @return \PDO
     */
    public static function getDb($database = null)
    {
        if (is_null(self::$db)) {
            try {
                if (is_readable(self::getDbFileName($database))) {
                    self::$db = new PDO('sqlite:'. self::getDbFileName($database));
                    if (Translation::useNormAndUp()) {
                        self::$db->sqliteCreateFunction('normAndUp', function ($s) {
                            return Translation::normAndUp($s);
                        }, 1);
                    }
                } else {
                    self::error($database);
                }
            } catch (Exception $e) {
                self::error($database);
            }
        }
        return self::$db;
    }

    /**
     * Summary of checkDatabaseAvailability
     * @param mixed $database
     * @return bool
     */
    public static function checkDatabaseAvailability($database)
    {
        if (self::noDatabaseSelected($database)) {
            for ($i = 0; $i < count(self::getDbList()); $i++) {
                self::getDb($i);
                self::clearDb();
            }
        } else {
            self::getDb($database);
        }
        return true;
    }

    /**
     * Summary of clearDb
     * @return void
     */
    public static function clearDb()
    {
        self::$db = null;
    }

    /**
     * Summary of querySingle
     * @param mixed $query
     * @param mixed $database
     * @return mixed
     */
    public static function querySingle($query, $database = null)
    {
        return self::getDb($database)->query($query)->fetchColumn();
    }


    /**
     * Summary of query
     * @param mixed $query
     * @param mixed $params
     * @param mixed $database
     * @return \PDOStatement
     */
    public static function query($query, $params = [], $database = null)
    {
        if (count($params) > 0) {
            $result = self::getDb($database)->prepare($query);
            $result->execute($params);
        } else {
            $result = self::getDb($database)->query($query);
        }
        return $result;
    }

    /**
     * Summary of queryTotal
     * @param mixed $query
     * @param mixed $columns
     * @param mixed $filter
     * @param mixed $params
     * @param mixed $n
     * @param mixed $database
     * @param mixed $numberPerPage
     * @return array{0: integer, 1: \PDOStatement}
     */
    public static function queryTotal($query, $columns, $filter, $params, $n, $database = null, $numberPerPage = null)
    {
        $totalResult = -1;

        if (Translation::useNormAndUp()) {
            $query = preg_replace("/upper/", "normAndUp", $query);
            $columns = preg_replace("/upper/", "normAndUp", $columns);
        }

        if (is_null($numberPerPage)) {
            global $config;
            $numberPerPage = $config['cops_max_item_per_page'];
        }

        if ($numberPerPage != -1 && $n != -1) {
            // First check total number of results
            $totalResult = self::countFilter($query, 'count(*)', $filter, $params, $database);

            // Next modify the query and params
            $query .= " limit ?, ?";
            array_push($params, ($n - 1) * $numberPerPage, $numberPerPage);
        }

        $result = self::getDb($database)->prepare(str_format($query, $columns, $filter));
        $result->execute($params);
        return [$totalResult, $result];
    }

    /**
     * Summary of queryFilter
     * @param mixed $query
     * @param mixed $columns
     * @param mixed $filter
     * @param mixed $params
     * @param mixed $n
     * @param mixed $database
     * @param mixed $numberPerPage
     * @return \PDOStatement
     */
    public static function queryFilter($query, $columns, $filter, $params, $n, $database = null, $numberPerPage = null)
    {
        if (Translation::useNormAndUp()) {
            $query = preg_replace("/upper/", "normAndUp", $query);
            $columns = preg_replace("/upper/", "normAndUp", $columns);
        }

        if (is_null($numberPerPage)) {
            global $config;
            $numberPerPage = $config['cops_max_item_per_page'];
        }

        if ($numberPerPage != -1 && $n != -1) {
            // Next modify the query and params
            $query .= " limit ?, ?";
            array_push($params, ($n - 1) * $numberPerPage, $numberPerPage);
        }

        $result = self::getDb($database)->prepare(str_format($query, $columns, $filter));
        $result->execute($params);
        return $result;
    }

    /**
     * Summary of countFilter
     * @param mixed $query
     * @param mixed $columns
     * @param mixed $filter
     * @param mixed $params
     * @param mixed $database
     * @return integer
     */
    public static function countFilter($query, $columns = 'count(*)', $filter = '', $params = [], $database = null)
    {
        // assuming order by ... is at the end of the query here
        $query = preg_replace('/\s+order\s+by\s+[\w.]+(\s+(asc|desc)|).*$/i', '', $query);
        $result = self::getDb($database)->prepare(str_format($query, $columns, $filter));
        $result->execute($params);
        $totalResult = $result->fetchColumn();
        return $totalResult;
    }
}
