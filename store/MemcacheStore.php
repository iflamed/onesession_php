<?php
require 'StoreInterface.php';

class MemcacheStore implements StoreInterface
{
	public static $_store = null;
	public static $config = array();
	public static $useMemcached = false;
	public $memcache;
	
	public function __construct($config,$useMemcached=false)
	{
	
		self::$config = $config;
		self::$useMemcached = $useMemcached;
		$this->setServers();
	}
	//单例方法,用于访问实例的公共的静态方法
	public static function getStore($storeConfig){
		if(!(self::$_store instanceof self)){
			$config = $storeConfig['config'];
			$useMemcached = isset($storeConfig['useMemcached'])?$storeConfig['useMemcached']:false;
			self::$_store = new self($config,$useMemcached);
		}
			return self::$_store;
	}
	private function setMemCache(){
        if($this->memcache!==null)
            return $this->memcache;
        else
        {
            $extension=self::$useMemcached ? 'memcached' : 'memcache';
            if(!extension_loaded($extension))
                throw new Exception("MemcacheSession requires PHP {$extension} extension to be loaded");
            return $this->memcache=self::$useMemcached ? new Memcached : new Memcache;
        }
    }

    private function setServers(){
        $this->setMemCache();
        foreach (self::$config as $key => $server) {
            $host = $server['host'];
            $port = isset($server['port'])?$server['port']:11211;
            $persistent = isset($server['persistent'])?$server['persistent']:true;
            $weight = isset($server['weight'])?$server['weight']:50;
            $timeout = isset($server['timeout'])?$server['timeout']:1;
            $retry_interval = isset($server['retry_interval'])?$server['retry_interval']:15;
            $status = isset($server['status'])?$server['status']:true;
            if(self::$useMemcached)
                $this->memcache->addServer($host,$port,$weight);
            else
                $this->memcache->addServer($host, $port,$persistent,$weight,$timeout,$retry_interval,$status);
        }
    }


	public function open(){
		return true;
	}

	public function set($id,$data,$lifetime=0){
		return self::$useMemcached?$this->memcache->set($id, json_encode($data), $lifetime):$this->memcache->set($id, json_encode($data), 0, $lifetime);
	}

	public function get($id){
		$sess = json_decode($this->memcache->get($id), true);
		return $sess;
	}

	public function delete($id){
		return $this->memcache->delete($id);
	}

	public function close(){
		(self::$useMemcached==false)?$this->memcache->close():'';
	}
}
?>