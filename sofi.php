<?php

/**
 * Sofi class file.
 *
 * @author Max Trifonov <mp.trifonov@gmail.com>
 * @link http://www.sofi.com/
 * @copyright Copyright &copy; 2013 Max Trifonov
 * @license http://www.sofi.com/license/
 * @version $Id: sofi.php 1 2013-08-23 03:00:00 mp.trifonov $
 * @package system
 * @since 1.0
 */
// File extensions
define('PHP_EXT', '.php');
define('PHP_PACK_EXT', '.phar');
define('LIB_EXT', '.php');
define('CLASS_EXT', '.class.php');
define('MODEL_EXT', '.model.php');
define('VIEW_EXT', '.phtml');
define('CONFIG_EXT', '.cfg');

/**
 * Путь к фреймворку
 */
define('SOFI_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);

/**
 * Defines the SOFI framework path.
 */
define('APPLICATIONS_DIR', 'applications' . DIRECTORY_SEPARATOR);

define('BASE_DIR', 'base' . DIRECTORY_SEPARATOR);
define('USES_DIR', BASE_DIR . 'uses' . DIRECTORY_SEPARATOR);
define('PARAMS_DIR', BASE_DIR . 'params' . DIRECTORY_SEPARATOR);
define('ASSEMBLY_DIR', BASE_DIR . 'assembly' . DIRECTORY_SEPARATOR);

/**
 * Defines the application run path.
 */
if (!defined('PUBLIC_PATH')) {
    $debug = debug_backtrace();
    define('PUBLIC_PATH', realpath(dirname($debug[0]['file'])) . DIRECTORY_SEPARATOR);
}

require_once SOFI_PATH . BASE_DIR . 'base.classes.php';

/**
 * 
 */
class Sofi {

    public static $useArguments = array();

    /**
     * 'general' - настройки фреймоврка, окружения, php
     * 'app_stack' - стек приложений
     * 'path' - пути подключения библиотек, компонентов, и п.р.
     * 'use' - базовые части системы
     * 'components' - компоненты системы
     * 
     * @var array 
     */
    protected static $_params = array(
        'general' => array(
            'assembly' => '',
            'session-start' => true,
            'time-zone' => 'Europe/Moscow',
            'time-limit' => 20,
            'charset' => 'UTF-8',
            'production' => true,
            'ini' => array()
        ),
        'app_stack' => array(),
        'app' => null,
        'path' => array(),
        'use' => array(),
        'include' => array(),
        'import' => array()
    );
    protected static $_app = null;
    protected static $_initialized = false;

    /**
     * @return string версия фреймворка
     */
    public static function getVersion() {
        return '1.0';
    }

    /**
     * @return string путь к файлам фреймворка
     */
    public static function getFrameworkPath() {
        return SOFI_PATH;
    }

    /**
     * Возвращает true если запущенно из консоли
     * @return boolean
     */
    public function isConsole() {
        return PHP_SAPI == 'cli' || (!isset($_SERVER['DOCUMENT_ROOT']) && !isset($_SERVER['REQUEST_URI']));
    }

    /**
     * Применение настроек
     * подключение расширений, компонентов и пр.
     */
    public static function applyParams($params = array()) {
        if (isset($params['path']) && is_array($params['path'])) {
            foreach ($params['path'] as &$path) {
                $path = self::convertPath($path);
            }
        }

        if (isset($params['include']) && is_array($params['include'])) {
            foreach ($params['include'] as $filename) {
                if (file_exists($filename))
                    require_once $filename;
            }
        }

        if (isset($params['use']) && is_array($params['use'])) {
            foreach ($params['use'] as $key => $useex) {
                if (is_int($key))
                    self::useBase($useex);
                else
                    self::useBase($key, $useex);
            }
        }

        if (isset($params['import']) && is_array($params['import'])) {
            foreach ($params['import'] as $import) {
                self::import($import);
            }
        }
    }

    protected static function initGeneral() {
        if (self::$_initialized)
            return false;

        foreach (self::$_params['general']['ini'] as $ini => $val) {
            if (is_int($ini))
                ini_set($val, true);
            else
                ini_set($ini, $val);
        }

        if (self::$_params['general']['session-start'])
            session_start();
        if (!self::$_params['general']['production'])
            define('SOFI_DEBUG', true);
        set_time_limit(self::$_params['general']['time-limit']);
        date_default_timezone_set(self::$_params['general']['time-zone']);
        mb_internal_encoding(self::$_params['general']['charset']);

        self::$_params['app_stack'][] = SOFI_PATH;

        self::$_initialized = true;

        return true;
    }

    /**
     * Иницилизация фреймворка,
     * настройки php, подключение базовых классов, библиотек и пр.,
     * установка режима работы
     * 
     * @param array массив с настройками
     */
    public static function init($params = array()) {
        self::$_params = self::mergeArray(self::$_params, $params);

        self::initGeneral();

        if (self::$_params['general']['assembly'] != '') {
            foreach (self::$_params['path'] as &$path) {
                $path = self::convertPath($path);
            }

            return self::assembly(self::$_params['general']['assembly']);
        }

        self::applyParams(self::$_params);

        return true;
    }

    /**
     * Добаляет путь к приложению в стек
     * 
     * @param string $path Путь к приложению
     * @param int $index Индекс в стеке приложений
     * @return boolean
     */
    static function addAppPath($path, $index = 0) {
        $apppath = $path;
        if (!is_dir($apppath)) {
            echo 'hi';
            $apppath = PUBLIC_PATH . $path;
            if (!is_dir($apppath)) {
                $apppath = APPLICATIONS_DIR . $path;
                if (!is_dir($apppath))
                    return false;
            }
        }

        if ($index == 0)
            array_unshift(self::$_params['app_stack'], 1);
        self::$_params['app_stack'][$index] = realpath($apppath);

        return true;
    }

    /**
     * Возвращает экземпляр приложения
     * 
     * @param mixed (TAplication|string) $app
     * @return TApplication
     * @throws SofiException
     */
    static function app($app = null) {
        if (is_object($app)) {
            self::$_app = $app;
            return self::$_app;
        }

        if (self::$_app == null) {
            if (is_object(self::$_params['app']))
                self::$_app = self::$_params['app'];
            else {
                $class = self::$_params['app'];
                try {
                    self::$_app = new $class();
                } catch (SofiException $exc) {
                    echo $exc->getTraceAsString();
                }
            }
        }

        if (is_object(self::$_app))
            return self::$_app;
        else {
            throw new SofiException();
        }
    }

    /**
     * Иницилизация по параметрам из файла
     * 
     * @param string $paramsFile
     * @return boolean
     */
    public static function initFromParamsFile($paramsFile) {
        $paramsFile .= PHP_EXT;

        $path = (file_exists($paramsFile)) ? $paramsFile :
                (file_exists(SOFI_PATH . PARAMS_DIR . $paramsFile)) ? SOFI_PATH . PARAMS_DIR . $paramsFile : null;

        if (is_readable($path)) {
            return self::init(include_once($path));
        }

        return false;
    }

    /**
     * Загрузка классов, файлов, библиотек по стандартным путям приложений
     * 
     * @param string $name
     * @param string $ext
     * @return boolean
     */
    public static function load($name, $ext = PHP_EXT) {
        foreach (self::$_params['app_stack'] as $key => $app) {
            $rname = self::convertPath($name) . $ext;

            $path = $app . BASE_DIR . 'classes' . DIRECTORY_SEPARATOR . $rname;
            if (file_exists($path)) {
                require_once $path;
                return true;
            }

            $path = $app . COMPONENTS_DIR . $rname;
            if (file_exists($path)) {
                require_once $path;
                return true;
            }
        }

        return false;
    }

    /**
     * Импорт классов, файлов, библиотек по подключаемым путям
     * @param string $file
     * @param string $ext
     * @param string $prefix
     * @return boolean
     */
    public static function import($file, $ext = '', $prefix = '') {
        $path = $prefix . self::convertPath($file) . $ext;

        foreach (self::$_params['app_stack'] as $key => $app) {
            if (file_exists($app . $path)) {
                require_once $app . $path;
                return true;
            } else {
                foreach (self::$_params['path'] as $spath) {
                    if (file_exists($app . $spath . $path)) {
                        require_once $app . $spath . $path;
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Подключение блоков use определяющих настройку системы и ее компоненты
     * @param string $ex
     * @param array $arguments
     * @return mixed
     */
    public static function useBase($ex, $arguments = null) {
        if ($arguments != null)
            self::$useArguments[$ex] = $arguments;

        return self::import($ex, PHP_EXT, USES_DIR);
    }

    /**
     * Магия )))
     * 
     * @param string $name
     * @param string $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments) {
        if (strpos($name, 'use') === 0) {
            return self::useBase(strtolower(substr($name, 3)), $arguments);
        } elseif (strpos($name, 'init') === 0) {
            return self::initFromParamsFile(strtolower(substr($name, 4)));
        }
    }

    /**
     * Возвращает параметры системы
     * 
     * @param type $key
     * @return array
     */
    public static function getParams($key = null) {
        return ($key === null) ? self::$_params : (isset(self::$_params[$key]) ? self::$_params[$key] : array());
    }

    /**
     * Создает и загружает сборку
     * 
     * @param string $filename
     * @param boolean $force
     * @return boolean
     */
    public static function assembly($filename, $force = false) {
        $filename = self::convertPath($filename) . PHP_PACK_EXT;

        if ($force || !file_exists($filename)) {
            // @TODO assembly libs
        } else {
            if (is_readable($filename))
                require_once $filename;
            else
                return false;

            return true;
        }

        return false;
    }

    /**
     * Вспомогательная функция преобразования пути к файлу
     * 
     * @param string $path
     * @param string $symbol
     * @return string
     */
    public static function convertPath($path, $symbol = '.') {
        return str_replace($symbol, DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Слияние массивов
     * @param array $a массив в который произойдет слияние
     * @param array $b array массив из которого будет выполняться слияние
     * массивов может быть задано несколько
     * @return array возвращает массив результата
     * @see mergeWith
     */
    public static function mergeArray($a, $b) {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            $next = array_shift($args);
            foreach ($next as $k => $v) {
                if (is_integer($k))
                    isset($res[$k]) ? $res[] = $v : $res[$k] = $v;
                else if (is_array($v) && isset($res[$k]) && is_array($res[$k]))
                    $res[$k] = self::mergeArray($res[$k], $v);
                else
                    $res[$k] = $v;
            }
        }
        return $res;
    }

}

?>
