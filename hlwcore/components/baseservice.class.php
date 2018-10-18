<?php

class hlw_components_baseservice
{
    protected $_time;
    protected $_ymd;
    protected $_error = array();
    private static $static_error = array('msg' => '', 'code' => 0);
    protected $id;
    protected $marter_table;
    protected $data = array();
    protected $data_many = array();
    protected $data_tmp = array();
    protected $model = array();
    protected $transaction = true;
    protected $dbentry = false;
    protected $lock = false;
    protected $lock_tables = null;
    private $unused_variables = array();

    /**
     * 构造函数
     */
    protected function __construct()
    {
        //设置当前系统时间
        $this->_time = time();
        $this->_ymd = date('Y-m-d', $this->_time);
    }

    /**
     * 设置当前主ID
     * 清除已经缓存的MODEL,及其数据
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->model = array();
        $this->data_many = array();
        $this->id = $id;
    }

    /**
     * 获取主表
     *
     * @return string
     */
    public function getMarterTable()
    {
        return $this->marter_table;
    }

    /**
     * 获取当前主ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 开启或关闭事务
     *
     * @param bool $isbt 是否开启事务
     */
    public function setTransaction($isbt)
    {
        $this->transaction = $isbt;
    }

    /**
     * 是否强行读取主表
     * 在执行get前执行
     *
     * @param bool $entry 是否强制读取主表
     */
    public function setDbEntry($entry = true)
    {
        $this->dbentry = $entry;
    }

    /**
     * 锁定方式访问指定数据表，如果为空则默认全部
     * 在执行get前执行
     * @param boolean $lock 是否锁定方式访问
     * @param array $tables 只对指定表开启锁定,支持多表
     */
    public function setLock($lock = true, $tables = null)
    {

        //强行设置主库
        $this->setDbEntry();

        $this->lock = $lock;

        $this->lock_tables = $tables;
    }

    /**
     * 写入错误信息
     * @param int $code
     * @param string $msg
     */
    protected function setError($code = 0, $msg = "")
    {
        $this->_error["code"] = $code;
        $this->_error["msg"] = $msg;
    }

    /**
     *设置错误信息并返回false
     *
     * @usage return $is_success ? true : $this->errorFalse($error_msg);
     * @param string $msg
     * @param int $code
     * @return bool
     */
    protected function errorFalse($msg = '', $code = 0)
    {
        $this->setError($code, $msg);

        return false;
    }

    /**
     * 获取错误信息
     *
     * @param string $type 参数：msg:错误信息,code:错误代码
     * @return string|int
     */
    public function getError($type = "msg")
    {
        return $this->_error[$type];
    }

    /**
     * 对象成员属性数据,支持数据模型中(HAS_ONE,BELONGS_TO)2种关系类型
     * 单个字段 $this->get('task_id') 等同于 $this->get('mk_task.task_id');
     * 数据集 $this->get('*') 等同于 $this->get('mk_task.*');
     * 其他数据，根据数据模型(relations)获取 $this->get('task_info.*') , $this->get('task_info.ip');
     *
     * @param string $fields
     * @return bool|array
     */
    public function get($fields = '*')
    {

        $fields = empty($fields) ? '*' : $fields;

        if (strpos($fields, '.') === false) {
            $fields = $this->getMarterTable() . '.' . $fields;
        }

        $key_tree = explode('.', $fields);

        $table = $key_tree[0];

        if (empty($this->id)) {

            //没有实际对象
            if ($key_tree[1] == '*' || $key_tree[1] == '') {
                return $this->data[$table];
            } else {
                return $this->data[$table][$key_tree[1]];
            }
        } else {
            $dbmodel = $this->model($table);
            if (!is_object($dbmodel)) {
                $this->setError(0, '没有获取到有效的数据模型');

                return false;
            }
            if ($key_tree[1] == '*' || $key_tree[1] == '') {
                return $dbmodel->getData("", $this->isLockTable($table));
            } else {
                return $dbmodel->getData($key_tree[1], $this->isLockTable($table));
            }
        }
    }

    /**
     * 设置对象成员属性数据,支持数组赋值
     *
     * @example $this->set(array('title'=>'标题','state'=>0));
     * 单字段赋值 $this->set('title','标题');
     *
     * @param string|array $fields
     * @param string|int $val
     * @return bool
     */
    public function set($fields, $val = '')
    {

        if (is_array($fields)) {
            //数组
            foreach ($fields as $key => $val) {
                if ($this->_set($key, $val) === false) {
                    $this->setError(0, '设置失败 ' . $this->getError());

                    return false;
                }
            }
        } else {
            return $this->_set($fields, $val);
        }

        return true;
    }

    /**
     * 设置单个值
     *
     * @param string $key
     * @param string|int $val
     * @return bool
     */
    protected function _set($key, $val)
    {

        $fields = empty($key) ? '*' : $key;

        if (strpos($fields, '.') === false) {
            $fields = $this->getMarterTable() . '.' . $fields;
        }

        $key_tree = explode('.', $fields);

        $table = $key_tree[0];

        if ($key_tree[1] == '*' || $key_tree[1] == '') {
            $this->setError(0, '不支持的数据格式');

            return false;
        } else {
            //当没有实际对象时,保存到临时数据
            if (empty($this->id)) {
                $this->data[$table][$key_tree[1]] = $val;
            }
            $this->data_tmp[$table][$key_tree[1]] = $val;
            $this->model($table)->set($key_tree[1], $val);
        }

        return true;
    }

    /**
     * 提交更新
     * 建议在使用多表更新时，为了保证数据的一致性，请在执行前建立事务。
     *
     * @param string $name 指定保存的名称
     * @return bool
     */
    public function save($name = '')
    {
        if (empty($name)) {
            //全部保存
            foreach ($this->data_tmp as $key => $val) {
                if ($this->model($key)->save() === false) {
                    $this->setError(0, $this->model($key)->getError() . $this->model($key)->getDbError());

                    return false;
                }
            }
            $this->data_tmp = array();
            $this->data = array();
        } else {
            //指定保存数据
            if ($this->model($name)->save() === false) {
                $this->setError(0, $this->model($name)->getError() . $this->model($name)->getDbError());

                return false;
            }
            unset($this->data_tmp[$name]);
            unset($this->data[$name]);
        }

        return true;
    }

    /**
     * 获取当前保存的Id
     * 不能使用lastInsertId()来获取db的新增ID，因为model里面的save如果操作了多个库的插入，就会导致返回不是主表的自增ID
     * 所以使用getPkid()来获取
     *
     * @return int
     */
    public function getLastId()
    {
        return $this->model()->getPkid();
    }

    /**
     * 获取扩展数据,一对多的数据关系
     *
     * @param string $name 关系名称
     * @return array|false
     */
    public function getMany($name)
    {
        //检查数据是否存在,如果存在就直接返回
        if (isset($this->data_many[$name])) {
            return $this->data_many[$name];
        }

        //重新获取数据
        $relations = $this->model()->relations();
        if (!isset($relations[$name])) {
            $this->setError(0, '无法在model找到对应关系');

            return false;
        }

        $condition = $this->model()->getRelationsCondition($name);
        if ($condition === false) {
            $this->setError(0, '无法获取关系条件，原因:' . $this->model()->getError());

            return false;
        }

        $condition .= '=\'' . $this->id . '\'';
        $result = $this->model($name)->select($condition);
        if ($result === false) {
            $this->setError(0, '获取数据失败 ' . $this->model()->getError());

            return false;
        }

        $this->data_many[$name] = $result->items;

        return $this->data_many[$name];
    }

    /**
     * 清除扩展数据
     *
     * @param string $table 指定别名
     */
    protected function clearManyData($table = '')
    {
        if (empty($table)) {
            $this->data_many = array();
        } else {
            unset($this->data_many[$table]);
        }
    }

    /**
     * 清除成员属性
     */
    protected function clearData()
    {
        $this->data = array();
        $this->model()->clearData();
    }

    /**
     * 清除已经设置的临时数据
     */
    protected function clearDataTmp()
    {
        $this->data_tmp = array();
    }

    /**
     * 获取相应MODEL
     * @param string $name 要实例化的MODEL
     * @return zbj_components_basemodel|false
     */
    protected function model($name = '')
    {

        //默认为主model
        if (empty($name)) {
            $name = $this->marter_table;
        }

        if (!isset($this->model[$name])) {
            if (!isset($this->model[$this->marter_table])) {
                $model_class = strpos($this->marter_table, 'model_') === false ? "model_$this->marter_table" : $this->marter_table;
                $this->model[$this->marter_table] = zbj_model_api::get($model_class, $this->id, $this->isLockTable($name) ? false : true);

                //是否强制读取主库
                if ($this->dbentry === true) {
                    $this->model[$this->marter_table]->setDbEntry();
                }

                return $this->model($name);
            }

            $relations = $this->model[$this->marter_table]->relations();
            if (!isset($relations[$name])) {
                $this->setError(0, '没有找到表关系' . $name);

                return false;
            }

            if ($relations[$name][2] == 'BELONGS_TO') {
                $id = $this->get($relations[$name][1]);
            } else {
                $id = $this->id;
            }

            $class_name = strpos($relations[$name][0], 'model_') === false ? 'model_' . $relations[$name][0] : $relations[$name][0];
            if (empty($id)) {

                $this->model[$name] = zbj_model_api::get($class_name);
            } else {

                $this->model[$name] = zbj_model_api::get($class_name, $id, $this->isLockTable($name) ? false : true);
            }
        }

        return $this->model[$name];
    }

    /**
     * 开启一个事务
     *
     * @param string $tables 要执行事务的数据库前缀,多个数据库用逗号分开
     * @return bool|null
     */
    protected function _beginTransaction($tables = '')
    {
        //是否开启事务
        if ($this->transaction !== true) {
            return false;
        }
        if (empty($tables)) {
            return false;
        }
        $list = explode(',', $tables);

        //建立主表事务
        $this->setLock(true, array());

        foreach ($list as $table) {
            $this->model()->beginTransaction($table);
        }

        return null;
    }

    /**
     * 提交一个事务
     *
     * @param string $tables 要执行事务的数据库前缀,多个数据库用逗号分开
     * @return bool|null
     */
    protected function _commit($tables = '')
    {
        //是否开启事务
        if ($this->transaction !== true) {
            return false;
        }
        if (empty($tables)) {
            return false;
        }
        $list = explode(',', $tables);
        foreach ($list as $table) {
            $this->model()->commit($table);
        }

        return null;
    }

    /**
     * 回滚一个事务
     *
     * @param string $tables 要执行事务的数据库前缀,多个数据库用逗号分开
     * @return bool|null
     */
    protected function _rollBack($tables = '')
    {
        //是否开启事务
        if ($this->transaction !== true) {
            return false;
        }
        if (empty($tables)) {
            return false;
        }
        $list = explode(',', $tables);
        foreach ($list as $table) {
            $this->model()->rollBack($table);
        }

        return null;
    }

    /**
     * 检查当前表访问是否为锁定方式
     *
     * @param string $model 要检查的别名
     * @return boolean
     */
    protected function isLockTable($model)
    {

        if ($this->lock == false) {
            return false;
        }

        //检查是否指定了表名
        if ($this->lock_tables === null) {
            return true;
        }

        //如果是主表，也会锁定
        if ($model == $this->getMarterTable()) {
            return true;
        }

        //如果指定了表名，是否在其中
        if (in_array($model, (array)$this->lock_tables)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取数据库表名
     *
     * @param string $table
     * @return string|null
     */
    public function getTableName($table = '')
    {
        $model = $this->model($table);
        if ($model) {
            return $model->tableName();
        }

        return null;
    }

    /**
     * 获取静态错误
     *
     * @param string $type
     * @return mixed
     */
    public static function getStaticError($type = 'msg')
    {
        return self::$static_error[$type];
    }

    /**
     * 设置静态错误
     *
     * @param string $msg
     * @param int $code
     * @return bool
     */
    public function setStaticErrorFalse($msg, $code = -1)
    {
        self::$static_error = array(
            'msg' => $msg,
            'code' => $code,
        );

        return false;
    }

    /**
     * 检查实例化id
     *
     * @return bool
     */
    public function checkPrimaryIntId()
    {
        if (!ctype_digit((string)$this->id)) {
            return $this->errorFalse('对象id必须为整数', -95);
        }
        $this->id = intval($this->id);
        if (!$this->get($this->model()->primarykey())) {
            return $this->errorFalse('该id不存在数据', -96);
        }

        return true;
    }

    /**
     * 添加未使用的变量
     *
     * @param mixed $unused_variable
     * @return $this
     */
    protected function addUnusedVariable($unused_variable)
    {
        $this->unused_variables[] = $unused_variable;

        return $this;
    }

}
