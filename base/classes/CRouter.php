<?php
/*
 * 
 * 
 * 
 */
class CRouter {
    protected $_check_path = null;

    protected $_aliases = array();
    protected $_default_route = null;
    protected $_routes = array();
    protected $_get = array();
    protected $_post = array();
    protected $_put = array();
    protected $_delete = array();
    protected $_xhr = array();
    
    protected $_map = array();
    
    
    /**
     *
     * @key_template string Общий шаблон для параметра 
     */
    var $key_template = '([а-яА-Я0-9a-zA-Z]+)';
    
    function __construct() {
        ;
    }
    
    function error404(){
        header($string);
    }


    /**
     * Устанавливает маршрут по умолчанию
     * 
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function defaultRoute($action) {
        $this->_default_route = $action;
        return $this;
    }

    /**
     * Добавляет точный маршрут и действие на него (не поддерживает шаблоны)
     * 
     * @param string $route Путь URI
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function match($route, $action) {
        $this->_aliases[$route] = $action;
        return $this;
    }

    /**
     * Добовляет маршрут и действие в независимости от протокола GET|POST|PUT|DELETE
     * 
     * @param string $route Путь URI
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function addRoute($route, $action) {
        $this->_routes[$route] = $action;
        return $this;
    }

    /**
     * Добовляет маршрут и действие для GET
     * 
     * @param string $route Путь URI
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function get($route, $action) {
        $this->_get[$route] = $action;
        return $this;
    }

    /**
     * Добовляет маршрут и действие для POST
     * 
     * @param string $route Путь URI
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function post($route, $action) {
        $this->_post[$route] = $action;
        return $this;
    }

    /**
     * Добовляет маршрут и действие для PUT
     * 
     * @param string $route Путь URI
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function put($route, $action) {
        $this->_put[$route] = $action;
        return $this;
    }

    /**
     * Добовляет маршрут и действие для DELETE
     * 
     * @param string $route Путь URI
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function delete($route, $action) {
        $this->_delete[$route] = $action;
        return $this;
    }

    /**
     * Добовляет маршрут и действие для Ajax запросов
     * 
     * @param string $route Путь URI
     * @param function|map|file $action Действие
     * @return \TRouter
     */
    function xhr($route, $action) {
        $this->_xhr[$route] = $action;
        return $this;
    }

  
    /**
     * Выполнение действия маршрута
     * 
     * @param type $action Действие
     * @param type $params Параметры
     */
    protected function exec($action, $params = array()) {
        if (is_callable($action)) {
            call_user_func_array($action, $params);
        } elseif (is_array($action)) {
            $this->_map = $action;
        } elseif (is_file($action)) {
            include($action);
        }else{
            return false;
        }
        
        return true;
    }

    /**
     * Проверка соответстия шаблону
     * 
     * @param type $key
     * @param type $action
     * @return boolean
     */
    protected function check_pattern($key, $action) {
        $rs = array();
        $pattern = '';
        $t = explode('{', str_replace('/', '\/', $key));
        foreach ($t as $val) {
            if ($pos = strpos($val, '}')){
                $templ = $this->key_template;
                
                $param = substr($val, 0, $pos);
                if ($pos2 = strpos($param, ':')){
                    $templ = substr($param, $pos2 + 1);
                }                
                $val = $templ . substr($val, $pos + 1);
            }
            $pattern .= $val;
        }
        $pattern .= '$';        
        if (mb_eregi($pattern, $this->_check_path, $rs)) {
            array_shift($rs);
            $this->exec($action, $rs);
            return true;
        }
        return false;
    }
    
    function getURI() {
        $uri = $_SERVER['REQUEST_URI'];
        $pos = strpos($uri, '?');
        if ($pos !== false) {
            $uri = substr($uri, 0, $pos);
        }
        return urldecode($uri);
    }
    
    /**
     * Группировка маршрутов по домену
     * 
     * @param type $route Шаблон маршрута (домена)     * 
     * @param type $action Действие
     * @return \TRouter
     */
    function domain($route, $action){
        $temp = $this->_check_path;
        $scheme = (isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS']=='on') ? 'https' : 'http';
        $this->_check_path = $scheme.'://'.$_SERVER['HTTP_HOST'];
        $this->check_pattern($route, $action);
        $this->_check_path = $temp;
        return $this;
    }

    /**
     * Разбор маршрута
     * 
     * @param TRequest|string|null $uri Маршрут
     * @return \TRouter
     */
    function process($uri = null) {
        if (is_string($uri)){
            $this->_check_path = $uri;
        }else{
            $this->_check_path = $this->getURI();
        }
        
        foreach ($this->_aliases as $key => $value) {
            if ($key == $this->_check_path) {
                $this->exec($value);
                return $this;
            }
        }
        
        mb_regex_encoding('UTF-8');
        foreach ($this->_routes as $key => $value) {
            if ($this->check_pattern($key, $value))
                return $this;
        }
        
        if ('GET' == $_SERVER['REQUEST_METHOD']) {
            $table = $this->_get;
        } elseif ('POST' == $_SERVER['REQUEST_METHOD']) {
            $table = $this->_post;
        } elseif ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'))) {
            $table = $this->_xhr;
        } elseif ('PUT' == $_SERVER['REQUEST_METHOD']) {
            $table = $this->_put;
        } elseif ('DELETE' == $_SERVER['REQUEST_METHOD']) {
            $table = $this->_delete;
        }

        foreach ($table as $key => $value) {
            if ($this->check_pattern($key, $value))
                return $this;
        }

        if ($this->_default_route !== null) {
            $this->exec($this->_default_route);
            return $this;
        }
        
        return $this;
    }

}
?>
