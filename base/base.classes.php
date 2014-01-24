<?php

/*
 * 
 */

class SofiException extends Exception
{
    
}

/**
 * 
 */
class Collection implements ArrayAccess
{
    public function __construct($array = null)
    {
        if (!is_null($array)) {
            foreach ($array as $key => $value) {
                $this->$key = $value;
            }            
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->$offset : null;
    }

    public function offsetSet($offset, $value)
    {
        if (!is_null($offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}

/*
 * 
 */
trait Provider{
    protected $_hold = array();
    
    function touch($name, $function){
        if (!isset($this->$name)){
            $this->_hold[$name] = $function;
        }else{
            $this->$name = $function();
        }
        return $this;
    }
    
    function __get($name){
        if (isset($this->_hold[$name])){
            if (is_callable($this->_hold[$name])){
                $this->$name = call_user_func_array($this->_hold[$name], array());
            }elseif (is_string($this->_hold[$name])){
                $class = $this->_hold[$name];
                $this->$name = new $class();
            }else{
                $this->$name = $this->_hold[$name];
            }
            
            unset($this->_hold[$name]);
            
            return $this->$name;
        }else{
            if (class_exists($name)){
                $this->$name = new $cname();
                return $this->$name;
            }            
        }
        
        return null;
    }
    
    function get($classname, $arguments = null){
        if (isset($this->$classname)) {
            // Сервис задан в хранаилище
            try {                
                // Если объект - возвращаем
                if (is_object($this->$classname) AND !($this->$classname instanceof Closure) )
                    return $this->$classname;
                // Если функция - выполняем и возвращаем результат
                if (is_callable($this->$classname)){
                    if ($arguments==null) return call_user_func_array($this->$classname, array());
                    else return call_user_func_array($this->$classname, $arguments);
                }
                // Если строка пытаемся создать объект
                if (is_string($this->$classname)){
                    $class = $this->$classname;
                    return new $class($arguments);
                }
                // Ошибка
                throw new SofiException('(SOFI SERVICES) Bad service ' . $classname);
            } catch (SofiException $ex) {
                $ex->out();
                return null;
            }
        }else{
            if (defined('DEBUG'))
                    debug_out($classname . ' -new service (function SeviceProvider->get(); )', 0);
            return $this->$classname;
        }
    }
    
    function __call($name, $arguments) {
        return $this->get($name, $arguments);
    }
    
}


?>
