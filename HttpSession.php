<?php
namespace Onesession;
if(!interface_exists('SessionHandlerInterface')){
    /**
     * SessionHandlerInterface
     *
     * Provides forward compatibility with PHP 5.4
     *
     * Extensive documentation can be found at php.net, see links:
     *
     * @see http://php.net/sessionhandlerinterface
     * @see http://php.net/session.customhandler
     * @see http://php.net/session-set-save-handler
     *
     * @author Drak <drak@zikula.org>
     */
    interface SessionHandlerInterface
    {
        /**
         * Open session.
         *
         * @see http://php.net/sessionhandlerinterface.open
         *
         * @param string $savePath    Save path.
         * @param string $sessionName Session Name.
         *
         * @throws \RuntimeException If something goes wrong starting the session.
         *
         * @return boolean
         */
        public function open($savePath, $sessionName);

        /**
         * Close session.
         *
         * @see http://php.net/sessionhandlerinterface.close
         *
         * @return boolean
         */
        public function close();

        /**
         * Read session.
         *
         * @param string $sessionId
         *
         * @see http://php.net/sessionhandlerinterface.read
         *
         * @throws \RuntimeException On fatal error but not "record not found".
         *
         * @return string String as stored in persistent storage or empty string in all other cases.
         */
        public function read($sessionId);

        /**
         * Commit session to storage.
         *
         * @see http://php.net/sessionhandlerinterface.write
         *
         * @param string $sessionId Session ID.
         * @param string $data      Session serialized data to save.
         *
         * @return boolean
         */
        public function write($sessionId, $data);

        /**
         * Destroys this session.
         *
         * @see http://php.net/sessionhandlerinterface.destroy
         *
         * @param string $sessionId Session ID.
         *
         * @throws \RuntimeException On fatal error.
         *
         * @return boolean
         */
        public function destroy($sessionId);

        /**
         * Garbage collection for storage.
         *
         * @see http://php.net/sessionhandlerinterface.gc
         *
         * @param integer $lifetime Max lifetime in seconds to keep sessions stored.
         *
         * @throws \RuntimeException On fatal error.
         *
         * @return boolean
         */
        public function gc($lifetime);
    }
}

class MemcacheSessionHandler implements SessionHandlerInterface
{
    
    private $lifetime = 0;
    private $store = null;
    public static $keyPrefix = 'sessions/';
    public static $config = array();
    public static $storeName = 'MemcacheStore';
    /**
     * init a memcache session handler, php>=5.4
     * @param  array $config 
     * array(
     *     'host'=>,
     *     'port'=>,
     *     'persistent'=>,
     *     'weight'=>,
     *     'timeout'=>,
     *     'retry_interval'=>,
     *     'status'=>
     * )
     * @return MemcacheSessionHandler54
     */
    public static function init($storeClassName,$config,$keyPrefix=''){
        self::$storeName = $storeClassName;
        require dirname(__FILE__).'/store/'.$storeClassName.".php";
        self::$config = $config;
        if ($keyPrefix) {
            self::$keyPrefix=$keyPrefix;
        }
        return static::getHandler();
    }

    public static function getHandler(){
        return new MemcacheSessionHandler();
    }


    /**
     * Constructor
     */
    public function __construct(){
        $this->setSessionHandler();
        $storeName = self::$storeName;
        $this->store = $storeName::getStore(self::$config);
    }


    public function setSessionHandler(){
        session_set_save_handler($this, true);
    }
 
    /**
     * Destructor
     */
    public function __destruct(){
        session_write_close();
        $this->store->close();
    }
 
    /**
     * Open the session handler, set the lifetime ot session.gc_maxlifetime
     * @return boolean True if everything succeed
     */
    public function open($savePath, $sessionName){
        $this->lifetime = ini_get('session.gc_maxlifetime');
        $this->store->open();
        return true;
    }
 
    /**
     * Read the id
     * @param string $id The SESSID to search for
     * @return string The session saved previously
     */
    public function read($id){
        $tmp = $_SESSION;
        $_SESSION = $this->store->get(self::$keyPrefix."{$id}");
        if(isset($_SESSION) && !empty($_SESSION) && $_SESSION != null){
            $new_data = session_encode();
            $_SESSION = $tmp;
            return $new_data;
        }else{
            return "";
        }
    }
 
    /**
     * Write the session data, convert to json before storing
     * @param string $id The SESSID to save
     * @param string $data The data to store, already serialized by PHP
     * @return boolean True if memcached was able to write the session data
     */
    public function write($id, $data){
        $_SESSION['cookie']=session_get_cookie_params();
        $tmp = $_SESSION;
        session_decode($data);
        $new_data = $_SESSION;
        $_SESSION = $tmp;
        return $this->store->set(self::$keyPrefix."{$id}", $new_data, $this->lifetime);
    }
 
    /**
     * Delete object in session
     * @param string $id The SESSID to delete
     * @return boolean True if memcached was able delete session data
     */
    public function destroy($id){
        return $this->store->delete(self::$keyPrefix."{$id}");
    }
 
    /**
     * Close gc
     * @return boolean Always true
     */
    public function gc($lifetime){
        return true;
    }
 
    /**
     * Close session
     * @return boolean Always true
     */
    public function close(){
        return true;
    }
}

/**
 * compatible for the php version less than 5.4
 */
class MemcacheSessionHandlerCompatible extends MemcacheSessionHandler
{
    /**
     * Instance a memcache session handler
     * @return Object Instance of MemcacheSessionHandlerCompatible
     */
    public static function getHandler(){
        return new MemcacheSessionHandlerCompatible();
    }

    /**
     * register the session handler
     */
    public function setSessionHandler(){
        session_set_save_handler(
            array($this, 'open'),    
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
        register_shutdown_function('session_write_close');
    }
 
}
/**
* 
*/
class HttpSession
{
    
    public static function init($storeClassName,$config,$keyPrefix='')
    {
        $phpVersion = phpVersion();
        if (version_compare($phpVersion, '5.4')!=-1){
            return MemcacheSessionHandler::init($storeClassName,$config,$keyPrefix);
        }else{
            return MemcacheSessionHandlerCompatible::init($storeClassName,$config,$keyPrefix);
        }
    }
}
?>
