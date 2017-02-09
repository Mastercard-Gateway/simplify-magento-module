<?php

namespace MasterCard\SimplifyCommerce;

use Psr\Log\LoggerInterface;


/** Custom logger */
class Log 
{
    protected $sclog = null;
    protected $path = null;
    protected $isDeveloperMode = false;

    public function __construct($isDeveloperMode, $path) {
        $this->isDeveloperMode = $isDeveloperMode;
        $this->path = $path;
        $this->sclog = new \Zend\Log\Logger();
        $this->sclog->addWriter(new \Zend\Log\Writer\Stream($this->path));
    }

    public function debug($message) {
        if ($this->isDeveloperMode) {
            $this->sclog->debug("Simplify: " . $message);
        }
        return $this;
    }

    public function info($message) {
        $this->sclog->info("Simplify: " . $message);
        return $this;
    }

    public function error($message) {
        $this->sclog->err("Simplify: " . $message);
        return $this;
    }

    public function warning($message) {
        $this->sclog->warn("Simplify: " . $message);
        return $this;
    }

}
