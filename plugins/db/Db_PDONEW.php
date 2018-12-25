<?php
/* {{{LICENSE
  +-----------------------------------------------------------------------+
  | SlightPHP Framework                                                   |
  +-----------------------------------------------------------------------+
  | This program is free software; you can redistribute it and/or modify  |
  | it under the terms of the GNU General Public License as published by  |
  | the Free Software Foundation. You should have received a copy of the  |
  | GNU General Public License along with this program.  If not, see      |
  | http://www.gnu.org/licenses/.                                         |
  | Copyright (C) 2008-2009. All Rights Reserved.                         |
  +-----------------------------------------------------------------------+
  | Supports: http://www.slightphp.com                                    |
  +-----------------------------------------------------------------------+
  }}} */

/**
 * @package SlightPHP
 * @subpackage SDb
 */
class Db_PDONEW extends DbObject {

    /**
     * @var string
     */
    public $host;

    /**
     * @var int
     */
    public $port = 3306;

    /**
     * @var string
     */
    public $user;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $database;

    /**
     * @var string
     */
    public $pri;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $dtype;

    /**
     * @var string
     */
    public $charset;

    /**
     * @var string
     */
    public $orderby;

    /**
     * @var string
     */
    public $groupby;

    /**
     * @var string
     */
    public $sql;

    /**
     * @var bool
     */
    public $count = false;

    /**
     * @var int
     */
    public $limit = 0;

    /**
     * @var int
     */
    public $page = 1;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $countsql;

    /**
     * @var int
     */
    private $affectedRows = 0;

    /**
     * 受影响记录数日志阀值
     * var int
     */
    const LOG_AFFECTED_ROWS = 10000;

    /**
     * @var array
     */
    public $error = array('code' => 0, 'msg' => "");

    /**
     * @var array $globals
     */
    static $globals;

    /**
     * 指定强行读取主从数据库
     * @var string
     */
    public static $dbentry;

    /**
     * @var bool
     */
    public $isShowDbError = true;

    /**
     * Db_PDONEW constructor.
     * @param string $prefix
     */
    public function __construct($prefix = "mysql") {
        $this->prefix = $prefix;
    }

    /**
     * init
     * @param array $params
     * @param string $table
     */
    public function init($params = array(), $table = '') {
        if (!empty($params['dbinfo'])) {
            $section_name = $params['dbinfo'];
            $table_array = array();
        } else {
            $table_array = explode('_', $table);
            $this->pri = $table_array[0];
            switch ($this->pri) {
                case 'mx': //慧猎网Boss数据库
                    $section_name = 'pinping';
                    break;
                case 'phpyun' : //慧猎网
                    $section_name = 'huiliewang';
                    break;
                case 'huilie'://慧猎网简历
                    $section_name = 'resume';
                    break;
                default:
                    $section_name = $this->pri;
            }
        }
        if (!empty($params['type'])) {
            $type = $params['type'];
        } else {
            $type = 'main';
        }
        $this->dtype = $type;

        //如果设置了强制入口
        if (!empty(self::$dbentry)) {
            $this->dtype = $type = self::$dbentry;
        }


        $dbConfig = SDb::getConfig($section_name, $type);
        if (!empty($dbConfig)) {
            if (!isset($dbConfig['port'])) {
                $dbConfig['port'] = '3306';
            }
            foreach ($dbConfig as $key => $value) {
                $this->$key = $value;
            }
            $this->key = $this->prefix . ':' . $this->host . ':' . $this->port . ':' . $this->user . ':' . $type . ':' . $table_array[0];
            if (!isset(Db_PDONEW::$globals[$this->key])) {
                Db_PDONEW::$globals[$this->key] = "";
            }
        } else {
            $log_data = array(
                'table' => $table,
                'dbinfo' => $section_name,
                'params' => $params,
            );
            $this->logSQLContext($log_data, 'configuration.log');
        }

        $this->debugMessage(PHP_EOL . "SelectDB[{$this->key}], table[{$table}], params[ " . json_encode($params) . ']' . PHP_EOL);
    }

    /**
     * is count
     *
     * @param boolean $count
     */
    public function setCount($count) {
        if ($count == true) {
            $this->count = true;
        } else {
            $this->count = false;
        }
    }

    /**
     * page number
     *
     * @param int $page
     */
    public function setPage($page) {
        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }
        $this->page = $page;
    }

    /**
     * page size
     *
     * @param int $limit 0 is all
     */
    public function setLimit($limit) {
        if (!is_numeric($limit) || $limit < 0) {
            $limit = 0;
        }
        $this->limit = $limit;
    }

    /**
     * group by sql
     *
     * @param string $group_by
     * eg:    setGroupby("groupby MusicID");
     *      setGroupby("groupby MusicID,MusicName");
     */

    /**
     * @param string $group_by 含GROUP BY
     */
    public function setGroupby($group_by) {
        $this->groupby = $group_by;
    }

    /**
     * order by sql
     *
     * @param string $order_by
     * eg:    setOrderby("order by MusicID Desc");
     */
    public function setOrderby($order_by) {
        $this->orderby = $order_by;
    }

    /**
     * select data from db
     *
     * @param $table
     * @param string|array $condition
     * @param string|array $item
     * @param string $group_by
     * @param string $order_by
     * @param string $left_join
     * @param array $params
     * @return bool|DbData
     */
    public function select(
    $table, $condition = "", $item = "*", $group_by = "", $order_by = "", $left_join = "", $params = array('type' => 'query'), $joins = "LEFT"
    ) {
        //{{{$item
        if ($item == "") {
            $item = "*";
        }
        if (is_array($table)) {
            $tmp = array();
            for ($i = 0; $i < count($table); $i++) {
                $tmp[] = trim($table[$i]);
            }
            $table = implode(" , ", $tmp);
        } else {
            $table = trim($table);
        }

        if (is_array($item)) {
            $item = implode(" , ", $item);
        }
        //}}}
        //{{{$condition
        $condition_string = $this->__quote($condition, "AND", $bind);

        if ($condition_string != "") {
            $condition_string = " WHERE " . $condition_string;
        }
        //}}}
        //{{{
        $join = "";
        if (is_array($left_join)) {
            foreach ($left_join as $key => $value) {
                $join .= " " . $joins . " JOIN $key ON $value ";
            }
        }
        //}}}
        //{{{
        $this->groupby = $group_by != "" ? $group_by : $this->groupby;
        $this->orderby = $order_by != "" ? $order_by : $this->orderby;

        $order_by_sql = "";
        $order_by_sql_tmp = array();
        if (is_array($order_by)) {
            foreach ($order_by as $key => $value) {
                if (!is_numeric($key)) {
                    $order_by_sql_tmp[] = $key . " " . $value;
                }
            }
        } else {
            $order_by_sql = $this->orderby;
        }
        if (count($order_by_sql_tmp) > 0) {
            $order_by_sql = " ORDER BY " . implode(",", $order_by_sql_tmp);
        }
        //}}}

        $limit = "";
        if ($this->limit != 0) {
            $limit = ($this->page - 1) * $this->limit;
            $limit = "LIMIT {$limit}, {$this->limit}";
        }
        $this->sql = "SELECT {$item} FROM {$table} {$join} {$condition_string} {$group_by} {$order_by_sql} {$limit}";
        //echo $this->sql."<br>";
        //echo $this->sql;

        if ($group_by) {
            $this->countsql = "SELECT count(1) totalSize FROM (SELECT 1 FROM {$table} {$join} {$condition_string}"
                    . " {$group_by}) as tmp_count";
        } else {
            $this->countsql = "SELECT count(1) totalSize FROM {$table} {$join} {$condition_string} {$group_by}";
        }


        $data = new DbData;

        $data->page = $this->page;
        $data->limit = $this->limit;
        $start = microtime(true);


        $data->limit = $this->limit;
        $data->items = $this->query($table, $this->sql, $bind, null, $params);
        if ($data->items === false) {
            //查询失败
            return false;
        }
        $data->pageSize = count($data->items);
        $end = microtime(true);
        $data->totalSecond = $end - $start;

        //}}}
        //{{{
        if ($this->limit != 0 && $this->count == true && $this->countsql != "") {
            $result_count = $this->query($table, $this->countsql, $bind, null, $params);
            $data->totalSize = $result_count[0]['totalSize'];
            $data->totalPage = ceil($data->totalSize / $data->limit);
        }
        //}}}
        //靠
        $this->setCount(false);
        $this->setPage(1);
        $this->setLimit(0);
        $this->setGroupby("");
        $this->setOrderby("");

        return $data;
    }

    /**
     * select one
     *
     * @param string $table
     * @param string|array $condition
     * @param string|array $item
     * @param string $group_by
     * @param string $order_by
     * @param string $left_join
     * @param array $params
     * @return array|bool
     */
    function selectOne(
    $table, $condition = "", $item = "*", $group_by = "", $order_by = "", $left_join = "", $params = array('type' => 'query')
    ) {
        $this->setLimit(1);
        $this->setCount(false);
        $data = $this->select($table, $condition, $item, $group_by, $order_by, $left_join, $params);

        //靠
        $this->setCount(false);
        $this->setPage(1);
        $this->setLimit(0);
        $this->setGroupby("");
        $this->setOrderby("");


        //2011.7.5 当查询失败才返回false,否则返回空
        if ($data === false) {
            return false;
        } else {
            if (isset($data->items[0])) {
                return $data->items[0];
            } else {
                return array();
            }
        }
    }

    /**
     * update data
     *
     * update("table",array('name'=>'myName','password'=>'myPass'),array('id'=>1));
     * update("table",array('name'=>'myName','password'=>'myPass'),array("password=$myPass"));
     * @param string $table
     * @param string|array $condition
     * @param string|array $item
     * @param array $params
     * @return bool|int
     */
    function update($table, $condition = "", $item = "", $params = array('type' => 'main')) {
        $value = $this->__quote($item, ",", $bind_v);

        $condition_string = $this->__quote($condition, "AND", $bind_c);
        if ($condition_string != "") {
            $condition_string = " WHERE " . $condition_string;
        }

        //2011.7.19 拒绝整表更新
        if (empty($condition_string)) {
            return false;
        }

        $this->sql = "UPDATE {$table} SET {$value} {$condition_string}";

        //echo $this->sql;
        if ($this->query($table, $this->sql, $bind_v, $bind_c, $params) !== false) {
            return $this->rowCount();
        } else {
            return false;
        }
    }

    /**
     * delete
     *
     * delete("table",array('name'=>'myName','password'=>'myPass'),array('id'=>1));
     * delete("table",array('name'=>'myName','password'=>'myPass'),array("password=$myPass"));
     * @param string $table
     * @param string|array $condition
     * @param array $params
     * @return bool|int
     */
    function delete($table, $condition = "", $params = array('type' => 'main')) {
        $condition_string = $this->__quote($condition, "AND", $bind);
        if ($condition_string != "") {
            $condition_string = " WHERE " . $condition_string;
        }

        //2011.7.19 拒绝整表删除
        if (empty($condition_string)) {
            return false;
        }

        $this->sql = "DELETE FROM {$table} {$condition_string}";
        if ($this->query($table, $this->sql, $bind, null, $params) !== false) {
            return $this->rowCount();
        } else {
            return false;
        }
    }

    /**
     * insert
     *
     * insert into zone_user_online values(2,'','','','',now(),now()) on duplicate key update onlineactivetime=CURRENT_TIMESTAMP;
     *
     * @param string $table
     * @param string|array $item
     * @param bool $is_replace
     * @param bool $is_delayed
     * @param array $update
     * @param array $params
     * @return array|bool|int
     */
    function insert(
    $table, $item = "", $is_replace = false, $is_delayed = false, $update = array(), $params = array('type' => 'main')
    ) {
        if ($is_replace == true) {
            $command = "REPLACE";
        } else {
            $command = "INSERT";
        }
        if ($is_delayed == true) {
            $command .= " DELAYED ";
        }

        $f = $this->__quote($item, ",", $bind_f);

        $this->sql = "$command INTO $table SET $f ";
        $v = $this->__quote($update, ",", $bind_v);
        if (!empty($v)) {
            $this->sql .= "ON DUPLICATE KEY UPDATE " . $v;
        }
        $r = $this->query($table, $this->sql, $bind_f, $bind_v, $params);
        if ($r === false) {        //2010.10.2 联系插入数据存在BUG，新增如果插入失败，返回false,
            return false;
        } elseif ($this->lastInsertId() > 0) {
            return $this->lastInsertId();
        } elseif ($this->affectedRows > 0) {
            return $this->affectedRows;
        } else {
            return $r;
        }
    }

    function sqlSplit($item) {
        if (empty($item))
            return false;
        $f = [];
        $insertKeys = '(`' . implode('`,`', array_keys($item[0])) . '`)';
        $insertValues = [];
        $e = 0;
        $k = 0;
        for ($i = 0; $i < count($item); $i++) {
            $insertValues[$k][] = '(\'' . implode("','", $item[$i]) . '\')';
            if ($e < 200) {
                $e++;
            } else {
                $e = 0;
                $k++;
            }
        }
        return ['key' => $insertKeys, 'values' => $insertValues];
    }

    function insertall(
    $table, $item = "", $is_replace = false, $is_delayed = false, $update = array(), $params = array('type' => 'main')
    ) {
        if ($is_replace == true) {
            $command = "REPLACE";
        } else {
            $command = "INSERT";
        }
        if ($is_delayed == true) {
            $command .= " DELAYED ";
        }

        //  $f = $this->__quote($item, ",", $bind_f);
        $f = $this->sqlSplit($item);

        if ($f == false)
            return false;
        foreach ($f['values'] as $v) {
            $values = implode(',', $v);
            $this->sql = "{$command} INTO {$table}{$f['key']} value{$values} ";
            $r = $this->query($table, $this->sql, $bind_f, $bind_v, $params);
        }
        if ($r === false) {        //2010.10.2 联系插入数据存在BUG，新增如果插入失败，返回false,
            return false;
        } elseif ($this->lastInsertId() > 0) {
            return $this->lastInsertId();
        } elseif ($this->affectedRows > 0) {
            return $this->affectedRows;
        } else {
            return $r;
        }
    }

    /**
     * query
     * @param string $table
     * @param string $sql
     * @param array $bind1
     * @param array $bind2
     * @param array $params
     * @return array|bool
     */
    function query($table, $sql, $bind1 = array(), $bind2 = array(), $params = array()) {
        //{{{

        $this->debugMessage("SQL:$sql\n", $bind1, $bind2, $params);

        if (empty($sql)) {
            //执行SQL不能为空
            return false;
        }

        //检查sql安全性
        if (!$this->isSafe($sql)) {
            //sql存在风险
            return false;
        }

        $lock = isset($params['lock']) ? ' ' . $params['lock'] : '';
        $stmt = $this->getPodObject($table, $params)->prepare($sql . $lock);

        if (!$stmt) {
            $this->error['code'] = Db_PDONEW::$globals[$this->key]->errorCode();
            if ($this->isShowDbError) {
                $this->error['msg'] = Db_PDONEW::$globals[$this->key]->errorInfo();
            } else {
                $this->error['msg'] = "mysql error";
                $this->addLog('Url:' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . ' mysqlerror:' . json_encode(Db_PDONEW::$globals[$this->key]->errorInfo()));
            }
            $this->debugMessage('<span style="color:red;">', $this->error, '</span>');
            return false;
        }
        if (!empty($bind1)) {
            foreach ($bind1 as $k => $v) {
                $stmt->bindValue($k, $v);
            }
        }
        if (!empty($bind2)) {
            foreach ($bind2 as $k => $v) {
                $stmt->bindValue($k + count($bind1), $v);
            }
        }
        if ($stmt->execute()) {
            $affected_rows = $this->affectedRows = $stmt->rowCount();
            if ($affected_rows >= self::LOG_AFFECTED_ROWS) {
                $log_data = array(
                    'table' => $table,
                    'sql' => $sql,
                    'affected_rows' => $affected_rows,
                    'bind1' => $bind1,
                    'bind2' => $bind2,
                    'params' => $params,
                );
                $this->logSQLContext($log_data, 'performance.log');
            }
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $this->error['code'] = $stmt->errorCode();
            if ($this->isShowDbError) {
                $this->error['msg'] = $stmt->errorInfo();
            } else {
                $this->error['msg'] = "mysql error";
                $this->addLog('Url:' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . ' mysqlerror:' . json_encode($stmt->errorInfo()));
            }
            $this->debugMessage('<span style="color:red;">', $this->error, '</span>');
        }

        return false;
    }

    /**
     * get last insert id
     * @return int
     */
    public function lastInsertId() {
        return Db_PDONEW::$globals[$this->key]->lastInsertId();
    }

    /**
     * @return int
     */
    public function rowCount() {
        return $this->affectedRows;
    }

    /**
     * @param string $sql
     * @param string $table
     * @return array|bool
     */
    public function execute($sql, $table = '') {
        return $this->query($table, $sql);
    }

    public function __connect($forceReconnect = false) {
        if (empty(Db_PDONEW::$globals[$this->key]) || $forceReconnect) {
            if (!empty(Db_PDONEW::$globals[$this->key])) {
                unset(Db_PDONEW::$globals[$this->key]);
            }
            try {
                Db_PDONEW::$globals[$this->key] = new PDO($this->prefix . ":dbname=" . $this->database . ";host=" . $this->host . ";port=" . $this->port, $this->user, $this->password);

                $this->debugMessage("new connect" . PHP_EOL);
            } catch (Exception $e) {
                //die("connect database error:\n".var_export($this,true));
                //写入日志
                $this->addLog('Host:' . $_SERVER['SERVER_NAME'] . ' Url:' . $_SERVER['REQUEST_URI'] . ' connect database error :' . $this->database . ',' . $this->dtype . ',' . $e->getMessage());

                //测试环境
                //die('connect database error :' .$this->database.','.$this->dtype.'<!--'.$e->getMessage().'-->');
                //生产环境
                die('<p align="center"></p>');
            }
        }
        if (!empty($this->charset)) {
            //$this->execute("SET NAMES ".$this->charset);
            Db_PDONEW::$globals[$this->key]->exec("SET NAMES " . $this->charset);
        }
    }

    public function __quote($condition, $split = "AND", &$bind) {

        $condiStr = "";
        if (!is_array($bind)) {
            $bind = array();
        }
        if (is_array($condition)) {
            $v1 = array();
            $i = 1;
            foreach ($condition as $k => $v) {
                if (!is_numeric($k)) {
                    if (strpos($k, ".") > 0) {
                        $v1[] = "$k = ?";
                    } else {
                        $v1[] = "`$k` = ?";
                    }
                    $bind[$i++] = $v;
                } else {
                    $v1[] = ($v);
                }
            }
            if (count($v1) > 0) {
                $condiStr = implode(" " . $split . " ", $v1);
            }
        } else {
            $condiStr = $condition;
        }
        $this->getFilter($condiStr);
        return $condiStr;
    }

    /**
     * @param string $table
     * @param array $params
     * @return PDO
     */
    public function getPodObject($table, $params = array('type' => 'query')) {
        //print_r($params);
        if (empty($params['dbinfo'])) {
            $this->init($params, $table);
            if (empty(Db_PDONEW::$globals[$this->key])) {
                //echo "建立新连接 \n <br>";
                $this->__connect(true);
            } else {

                $this->debugMessage("connect state : " . PHP_EOL, Db_PDONEW::$globals[$this->key]->getAttribute(PDO::ATTR_CONNECTION_STATUS));

                //如果已经有连接，检查连接状态
                /*
                  if(!Db_PDONEW::$globals[$this->key]->getAttribute(PDO::ATTR_CONNECTION_STATUS)) {

                  //强制重新连接
                  $this->debugMessage("原连接断开，重新重新连接 \n <br>");

                  $this->__connect(true);
                  }
                 */
            }
        } else {
            $this->init($params, $table);
            $this->__connect(true);
        }

        //var_dump(Db_PDONEW::$globals[$this->key]->getAttribute(PDO::ATTR_CONNECTION_STATUS));
        //echo $this->key . " \n<br>";

        return Db_PDONEW::$globals[$this->key];
    }

    /**
     * 开始事务
     *
     * @param string $table
     * @param array $params
     * @return bool
     */
    public function beginTransaction($table, $params = array('type' => 'main')) {
        $this->setDbEntry();
        return $this->getPodObject($table, $params)->beginTransaction();
    }

    /**
     * 提交事务
     * @param string $table
     * @param array $params
     * @return bool
     */
    public function commit($table, $params = array('type' => 'main')) {
        return $this->getPodObject($table, $params)->commit();
    }

    /**
     * 回滚事务
     * @param string $table
     * @param array $params
     * @return bool
     */
    public function rollBack($table, $params = array('type' => 'main')) {
        return $this->getPodObject($table, $params)->rollBack();
    }

    /**
     * 强制指定读取主从数据库
     *
     * 一旦设置不论读写都指向了设置的数据库，请谨慎使用
     * 在model及service使用时，只有在第一次获取数据时有效
     *
     */
    public function setDbEntry() {
        self::$dbentry = 'main';
    }

    /**
     * 写入日志文件 检查文件如果存在就建立日志
     *
     * @param string $msg
     * @param string $file
     * @return bool
     */
    public function addLog($msg, $file = 'db_error.log') {
        $dir = '/var/log/gandianli/dberror/';
        $file = $dir . $file . '.' . date('Y-m-d');
        if ((is_dir($dir) || @mkdir($dir, 0755, true)) && is_writable($dir)) {
            $data = 'Date:' . date('Y-m-d H:i:s') . ' ' . $msg . "\n";
            $f = fopen($file, 'a+');
            fwrite($f, $data, strlen($data));
            fclose($f);
            return true;
        }
        return false;
    }

    /**
     * 断开所有链接
     * @return void
     */
    public function disconnect() {
        Db_PDONEW::$globals = null;
    }

    /**
     * 检查sql的是否安全
     * @param string $sql
     * @return bool
     */
    public function isSafe($sql) {

        $clean = '';
        $error = '';
        $old_pos = 0;
        $pos = -1;

        while (true) {
            $pos = strpos($sql, '\'', $pos + 1);
            if ($pos === false) {
                break;
            }
            $clean .= substr($sql, $old_pos, $pos - $old_pos);

            while (true) {
                $pos1 = strpos($sql, '\'', $pos + 1);
                $pos2 = strpos($sql, '\\', $pos + 1);
                if ($pos1 === false) {
                    break;
                } elseif ($pos2 == false || $pos2 > $pos1) {
                    $pos = $pos1;
                    break;
                }

                $pos = $pos2 + 1;
            }
            $clean .= '$s$';

            $old_pos = $pos + 1;
        }

        $clean .= substr($sql, $old_pos);

        $clean = trim(strtolower(preg_replace(array('~\s+~s'), array(' '), $clean)));

        //老版本的Mysql并不支持union，常用的程序里也不使用union，但是一些黑客使用它，所以检查它
        if (strpos($clean, ' union ') !== false && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0) {
            $fail = true;
            $error = "union detect";
        } elseif (strpos($clean, '/*') > 2 || strpos($clean, '--') !== false || strpos($clean, '#') !== false) {
            //发布版本的程序可能比较少包括--,#这样的注释，但是黑客经常使用它们
            $fail = true;
            $error = "comment detect";
        } elseif (strpos($clean, ' sleep') !== false && preg_match('~(^|[^a-z])sleep($|[^[a-z])~s', $clean) != 0) {
            //这些函数不会被使用，但是黑客会用它来操作文件，down掉数据库
            $fail = true;
            $error = "slown down detect";
        } elseif (strpos($clean, 'benchmark') !== false && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0
        ) {
            $fail = true;
            $error = "slown down detect";
        } elseif (strpos($clean, 'load_file') !== false && preg_match('~(^|[^a-z])load_file($|[^[a-z])~s', $clean) != 0
        ) {
            $fail = true;
            $error = "file fun detect";
        } elseif (strpos($clean, 'into outfile') !== false && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~s', $clean) != 0
        ) {
            $fail = true;
            $error = "file fun detect";
            //}elseif (preg_match('~\([^)]*?select~s', $clean) != 0){
            //老版本的MYSQL不支持子查询，我们的程序里可能也用得少，但是黑客可以使用它来查询数据库敏感信息
            //	$fail = true;
            //	$error="sub select detect";
        }

        if (!empty($fail)) {
            //写入日志
            $this->addLog($error . ' ' . $_SERVER['REMOTE_ADDR'] . ' ' . $_SERVER['HTTP_USER_AGENT'] . ' ' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] . ' SQL:' . $sql, "inject_{$_SERVER['HOSTNAME']}.log");
            return false;
        } else {
            return true;
        }
    }

    /**
     * 记录SQL查询上下文
     *
     * @param array $data
     * @param string $filename
     * @return bool
     */
    private function logSQLContext($data, $filename) {
        if ($data && is_array($data) && $filename) {
            $data['url'] = '';
            $data['time'] = time();
            $data['ip'] = '';
            $data['ua'] = hlw_lib_BaseUtils::getStr($_SERVER['HTTP_USER_AGENT']);
            return $this->addLog(json_encode($data), $filename);
        } else {
            return false;
        }
    }

    /**
     * debug message
     */
    private function debugMessage() {
        if (defined("DEBUG") && $this->isShowDbError === true) {
            $args = func_get_args();
            foreach ($args as $v) {
                if (is_string($v)) {
                    echo $v;
                } else {
                    var_dump($v);
                }
            }
            return true;
        }
        return false;
    }

    public function getFilter($str) {
        $getfilter = "\\<.+javascript:window\\[.{1}\\\\x|<.*=(&#\\d+?;?)+?>|<.*(data|src)=data:text\\/html.*>|\\b(alert\\(|confirm\\(|expression\\(|prompt\\(|benchmark\s*?\(.*\)|sleep\s*?\(.*\)|load_file\s*?\\()|<[a-z]+?\\b[^>]*?\\bon([a-z]{4,})\s*?=|^\\+\\/v(8|9)|\\b(chr|mid)\\b\\s*?([\\(\\)'\"\\d]+?=[\\(\\)'\"\\d]+?|[\\(\\)'\"a-zA-Z]+?=[\\(\\)'\"a-zA-Z]+?|>|<|\s+?[\\w]+?\\s+?\\bin\\b\\s*?\(|\\blike\\b\\s+?[\"'])|\\/\\*.*\\*\\/|<\\s*script\\b|\\bEXEC\\b|UNION.+?SELECT\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)|UPDATE\s*(\(.+\)\s*|@{1,2}.+?\s*|\s+?.+?|(`|'|\").*?(`|'|\")\s*)SET|INSERT\\s+INTO.+?VALUES|(SELECT|DELETE)@{0,2}(\\(.+\\)|\\s+?.+?\\s+?|(`|'|\").*?(`|'|\"))FROM(\\(.+\\)|\\s+?.+?|(`|'|\").*?(`|'|\"))|(CREATE|ALTER|DROP|TRUNCATE)\\s+(TABLE|DATABASE)";
        if (preg_match("/" . $getfilter . "/is", $str, $arr) == 1) {
            $log = array(
                'sql' => $str,
                'agent' => hlw_lib_BaseUtils::getStr($_SERVER['HTTP_USER_AGENT']),
                'ip' => hlw_lib_BaseUtils::getIp(),
                'time' => time(),
                'data' => $arr[0],
            );
            $file = 'risk_sql_log';
            $this->addLog(json_encode($log), $file);
        }
        return true;
    }

}
