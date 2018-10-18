<?php
class SFilter
{
    public static $SF;
    private static $DICT_LV1 = '/var/www/SlightPHP/plugins/sf_dict/dict1.txt';
    private static $DICT_LV2 = '/var/www/SlightPHP/plugins/sf_dict/dict2.txt';
    private static $DICT_BASE = 'sf_dict/dict_base.txt';
    private static $REL;
    private static $BANNED;
    private static $_cache;
    private static $unused_variables = array();
    private static $sensitive_words = array();
    private static $app_id = '7a57a5a743894a0e';
    private static $app_secret = '21232f297a57a5a743894a0e4a801fc3';

    /**
     * 检查屏蔽
     *
     * @param string $strings 过滤字符,
     * @param bool|false $retype 是否替换
     * @param string $dict_type 使用词典类型
     * @return bool|int|string -1无内容,0无匹配,1有匹配
     */
    public static function check($strings, $retype = false, $dict_type = '1')
    {
        if ($strings) {
            self::addUnusedVariables($dict_type);
            self::addUnusedVariables(SFilter::$DICT_BASE);
            self::addUnusedVariables(SFilter::$DICT_LV1);
            self::addUnusedVariables(SFilter::$DICT_LV2);
            self::addUnusedVariables(SFilter::$REL);
            return self::_checkKey($strings, $retype);
        } else {
            return -1;
        }
    }

    /**
     * 检查是否含过滤关键词
     *
     * @param string $str 需要检查的内容
     * @param int $level 检查关键词等级,等级0,不加载关键词库，等级1只针对非法关键词，等级2含后台配置的关键词字典
     * @param array $add_key 把指定的关键词添加进去
     * @return true|false
     */
    public static function checkey($str = '', $level = 1, $add_key = array())
    {
        self::addUnusedVariables($level);
        self::addUnusedVariables($add_key);
        return self::_checkKey($str);
    }

    /**
     * 替换敏感关键词
     *
     * @param string $str 需要检查的内容
     * @param int $level 检查关键词等级,等级1只针对非法关键词，等级2含后台配置的关键词字典
     * @param array $add_key 把指定的关键词添加进去
     * @return string
     */
    public static function replace($str = '', $level = 1, $add_key = array())
    {
        $banned = SFilter::getDict($level, 2, $add_key);

        if (empty($banned)) {
            return $str;
        }

        $banned_list = array_chunk($banned, 1500);

        foreach ($banned_list as $banned_str) {
            $str = str_replace($banned_str, '**', $str);
        }

        return $str;
    }

    /**
     * 获取数据库的关键词表
     *
     * @return false | array
     */
    public static function getDbDict()
    {
        $kw_array = array();
        $cont = SFilter::exp_dict(); 
        if (empty($cont)) {
            return $kw_array;
        }

        $tmp_array = explode("\n", $cont);
        foreach ((array)$tmp_array as $kw) {
            if (empty($kw)) {
                continue;
            }
            $kw_array[] = $kw;
        }

        if (!empty($kw_array)) {
            $kw_array = SFilter::getFullPreg($kw_array);
        }

        return $kw_array;
    }

    /**
     * 获取本地关键词表
     *
     * @param int $type base | prep
     * @return false | array
     */
    public static function getLocalDict($type)
    {
        $type = strtolower($type);
        if (!in_array($type, array('base', 'preg'))) {
            return false;
        }

        if (isset(SFilter::$_cache[$type])) {
            return SFilter::$_cache[$type];
        }

        $dict_path = dirname(__FILE__) . '/sf_dict/dict_' . $type . '.txt';
        if (!is_file($dict_path)) {
            return false;
        }

        $cont = @file_get_contents($dict_path);
        if (empty($cont)) {
            return false;
        }

        $kw_array = array();
        $tmp_array = explode("\n", $cont);
        foreach ((array)$tmp_array as $kw) {
            if (empty($kw)) {
                continue;
            }
            $kw_array[] = $kw;
        }

        if ($type == 'preg' && !empty($kw_array)) {
            $kw_array = SFilter::getFullPreg($kw_array);
        }

        if (!empty($kw_array)) {
            $kw_array = array_unique($kw_array);
            $kw_array = array_chunk($kw_array, 2000);
        }
        SFilter::$_cache[$type] = $kw_array;

        return $kw_array;
    }

    /**
     * 获取完整的正则
     * @param array $keyword_array
     * @return array
     */
    protected static function getFullPreg(array $keyword_array)
    {
        foreach ($keyword_array as &$kw) {
            $kw = preg_replace("/\\\{(\d+)\\\}/", ".{0,\\1}", preg_quote($kw, '/'));
            $kw = '/' . $kw . '/i';
        }

        return $keyword_array;
    }


    /**
     * 获取关键词表
     * @param int $level 检查关键词等级,等级1只针对非法关键词，等级2含后台配置的关键词字典
     * @param int $type 1:匹配性内容 2:替换性内容
     * @param array $add_key 把指定的关键词添加进去
     * @return string
     */
    public static function getDict($level = 1, $type = 1, $add_key = array())
    {

        self::addUnusedVariables($level);
        if (!empty(SFilter::$BANNED[$type]) && empty($add_key)) {
            return SFilter::$BANNED[$type];
        }

        $banned = array();
        $dict = self::_getAllKey();

        if (!empty($add_key)) {
            $dict = array_merge($dict, $add_key);
        }
        $dict = (array) $dict;
        foreach ($dict as $key => $v) {
            $key = trim($key);
            if (empty($key)) {
                continue;
            }
            $banned[] = $key;
        }

        if (empty($banned)) {
            return null;
        } else {
            return SFilter::$BANNED[$type] = $banned;
        }
    }

}
