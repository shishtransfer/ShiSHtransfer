<?php
class ConfigParser{
    private $config;
    private $whoami;
    function __construct($whoami){
        $this->config = json_decode(file_get_contents("./config.json"),true);
        $this->whoami = $whoami;
    }
    function exists($acc){
        return isset($this->config["MAIN"][$acc]);
    }
    function whoami(){
        return $this->whoami;
    }
    function toPort($acc){
        return $this->config["MAIN"][$acc]??null;
    }
    function getRandAccc(){
        return array_rand($this->config["MAIN"]);
    }
    function getBackup(){
        return $this->config["BACKUP"];
    }
    function generateSystemctlTemplate(){
        foreach ($this->config["MAIN"] as $user => $port) {
            echo "sfa@$user ";
        }
    }
}