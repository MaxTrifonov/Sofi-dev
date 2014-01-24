<?php

trait Settings{

    protected $_settings = array();

    function setSettings($settings = array()) {
        foreach ($settings as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    $this->_settings[$key][$key2] = $value2;
                }
            } else {
                $this->_settings[$key] = $value;
            }
        }
        return $this;
    }

    function getSettings($options = null) {
        $settings = $this->_settings;
        if (is_array($options)) {
            foreach ($options as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $key2 => $value2) {
                        $settings[$key][$key2] = $value2;
                    }
                } else {
                    $settings[$key] = $value;
                }
            }
        }
        return $settings;
    }

    function init($settings = null) {
        if ($settings !== null) {
            $this->setSettings($settings);
        }
        return $this;
    }

}


?>
