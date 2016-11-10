<?php

namespace Eta\Addon\Db;

use Eta\Core\Config as AppConfig;

class Tunnel {

    protected $config = [];

    public function __construct($tunnelName) {
        $this->config = AppConfig::getInstance()->get('tunnel',$tunnelName);
    }

    public function connect() {
        $cmd = "autossh -f {$this->config['user']}@{$this->config['host']} -L {$this->config['local_port']}:{$this->config['remote_ip']}:{$this->config['remote_port']} -N -i ".getcwd()."/application/data/keys/{$this->config['private_key']} -p{$this->config['port']} -o \"StrictHostKeyChecking no\"";
        passthru($cmd);
        sleep(1);
    }
}
