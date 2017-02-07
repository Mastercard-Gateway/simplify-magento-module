<?php

namespace MasterCard\SimplifyCommerce;

use Psr\Log\LoggerInterface;


/** Custom logger */
class Log 
{
    protected $_sclog = null;
    protected $_path = null;
    protected $_isDeveloperMode = false;

    public function __construct($isDeveloperMode, $path) {
        $this->_isDeveloperMode = $isDeveloperMode;
        $this->_path = $path;

        if ($this->canLog()) {
            $this->_sclog = new \Zend\Log\Logger();
            $this->_sclog->addWriter(new \Zend\Log\Writer\Stream($this->_path));
        }
    }

    public function info($message) {
        if ($this->canLog()) {
            $this->_sclog->info("Simplify: " . $message);
        }
        return $this;
    }

    public function error($message) {
        if ($this->canLog()) {
            $this->_sclog->err("Simplify: " . $message);
        }
        return $this;
    }

    public function warning($message) {
        if ($this->canLog()) {
            $this->_sclog->warn("Simplify: " . $message);
        }
        return $this;
    }

    public function canLog() {
        return ($this->_isDeveloperMode);
    }

}
