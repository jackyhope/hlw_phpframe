<?php

class SGui {

    static private $smarty;

    /**
     * is rake project
     * @var bool
     */
    private $rakeZbjProject = false;

    /**
     * is rake project
     * @var bool
     */
    private static $rakeZbjProjectRender = false;
    private $smartyVariable = array();

    /**
     * tpl dir
     * @var string
     */
    private $tplDir = '';

    static public function &smarty() {
        if (!isset(self::$smarty)) {
            self::$smarty = new Smarty();
        }
        return self::$smarty;
    }

    /**
     * get tpl dir
     * @return string
     */
    public function getTplDir() {
        return $this->tplDir;
    }

    /**
     * set tpl dir
     * @param $tplDir
     * @return $this
     */
    public function setTplDir($tplDir) {
        $this->tplDir = $tplDir;

        return $this;
    }

    /**
     * check if is rake project
     * @return boolean
     */
    public function isRakeZbjProject() {
        return $this->rakeZbjProject;
    }

    /**
     * 302 redirect
     * @param string $url
     * @param bool $permanent
     * @return null
     */
    public function redirect($url, $permanent = false) {
        if ($permanent) {
            header("HTTP/1.1 301 Moved Permanently");
        }
        header('Location:' . $url);
        exit;
    }

    /**
     * assign value
     * @param string $key
     * @param mixed $value
     */
    public function ass($key, $value) {
        if ($this->isRakeZbjProject()) {
            $this->rakeTplVar[$key] = $value;
        } else {
            $this->smartyVariable[$key] = $value;
        }
    }

    public function getSmartyVariable() {
        return $this->smartyVariable;
    }

}

?>
