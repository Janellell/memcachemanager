<?PHP
#################################################################################
## Developed by Manifest Interactive, LLC                                      ##
## http://www.manifestinteractive.com                                          ##
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ##
##                                                                             ##
## THIS SOFTWARE IS PROVIDED BY MANIFEST INTERACTIVE 'AS IS' AND ANY           ##
## EXPRESSED OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE         ##
## IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR          ##
## PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL MANIFEST INTERACTIVE BE          ##
## LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR         ##
## CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF        ##
## SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR             ##
## BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,       ##
## WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE        ##
## OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,           ##
## EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.                          ##
## ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ ##
## Author of file: Peter Schmalfeldt                                           ##
#################################################################################

/**
 * @category Memcached Manager
 * @package MemcachedManager
 * @author Peter Schmalfeldt <manifestinteractive@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://code.google.com/p/memcachemanager/
 * @link http://groups.google.com/group/memcachemanager
 */

/**
 * Begin Document
 */

class MemcachedManager {
	
	/**
     * Main Memcache Object
     *
     * @var    int
     * @access private
     */

	private $mcd;
	
	/**
     * Key Prefix
     *
     * @var    int
     * @access private
     */
	private $prefix;
	
	/**
     * Database Object to store tags
     *
     * @var    int
     * @access private
     */
	private $tagdb;
	
	/**
	 * Creates a Memcached instance representing the connection to the memcache servers. 
     *
     * @param string $persistentid 	By default the Memcached instances are destroyed at the end of the request. 
	 *								To create an instance that persists between requests, use persistent_id to 
	 *								specify a unique ID for the instance. All instances created with the same 
	 *								persistent_id  will share the same connection.   
     * @access 	public
     */	
	public function __construct($persistentid='', $prefix=''){
		
		$this->prefix = $prefix;
		$this->keys = array();
		
		// tagging db connection
		$dbhost = ''; // you mysql host
		$dbuser = ''; // you mysql username
		$dbpass = ''; // you mysql password
		$dbname = ''; // you mysql database
		
		if(!empty($dbhost) && !empty($dbuser) && !empty($dbpass) && !empty($dbname)){
			$this->tagdb = mysql_connect($dbhost,$dbuser,$dbpass) or die("Could not connect to database");
			if(!mysql_select_db($dbname,$this->tagdb)) die("Could not connect to database.");
		}
		
		if(class_exists('Memcached')) {
			$this->mcd = (!empty($persistentid)) ? new Memcached($persistentid):new Memcached();
			/* 	
				OPT_DISTRIBUTION: Specifies the method of distributing item keys to the servers. Currently supported methods are 
				modulo and consistent hashing. Consistent hashing delivers better distribution and allows servers to be added to 
				the cluster with minimal cache losses. 
				
				DISTRIBUTION_CONSISTENT: Consistent hashing key distribution algorithm.
			*/
			$this->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
			/* 	
				OPT_LIBKETAMA_COMPATIBLE: Enables or disables compatibility with libketama-like behavior. When 
				enabled, the item key hashing algorithm is set to MD5 and distribution is set to be weighted 
				consistent hashing distribution. This is useful because other libketama-based clients (Python, 
				Ruby, etc.) with the same server configuration will be able to access the keys transparently. 
			*/
			$this->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
			/*
				OPT_COMPRESSION: Enables or disables payload compression. When enabled, item values longer 
				than a certain threshold (currently 100 bytes) will be compressed during storage and 
				decompressed during retrieval transparently. You cannot use append or preprend with 
				compression turned on.
			*/
			$this->setOption(Memcached::OPT_COMPRESSION, false);
			/*
				OPT_HASH: Specifies the hashing algorithm used for the item keys. The valid values are supplied via 
				Memcached::HASH_* constants. Each hash algorithm has its advantages and its disadvantages. 
				Go with the default if you don't know or don't care.
				
				HASH_MURMUR: Murmur item key hashing algorithm.
			*/
			$this->setOption(Memcached::OPT_HASH, Memcached::HASH_MURMUR);
			/*
				OPT_PREFIX_KEY: This can be used to create a "domain" for your item keys. The value specified 
				here will be prefixed to each of the keys. It cannot be longer than 128 characters and will 
				reduce the maximum available key size. The prefix is applied only to the item keys, not to the 
				server keys.
			*/
			if(!empty($prefix)) $this->setOption(Memcached::OPT_PREFIX_KEY, $prefix);
		}
		else $this->_triggerError('PHP Class "Memcached" does not exist!', E_USER_ERROR);
	}

   	/**
     * Add an item under a new key. 
	 * 
	 * Similar to set, but the operation fails if the key already exists. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->add('mykey1', $myarray1, 30);
	 * ?>
 	 * </code>
     *
     * @param string $key 		The key under which to store the value.
	 * @param mixed $value 		The value to store. 
	 * @param int $expiration	Some storage commands involve sending an expiration value (relative to an item or 
	 *							to an operation requested by the client) to the server. In all such cases, the 
	 *							actual value sent may either be Unix time (number of seconds since January 1, 1970, 
	 *							as an integer), or a number of seconds starting from current time. In the latter 
	 *							case, this number of seconds may not exceed 60*60*24*30 (number of seconds in 30 
	 *							days); if the expiration value is larger than that, the server will consider it to 
	 *							be real Unix time value rather than an offset from current time. If the expiration 
	 *							value is 0 (the default), the item never expires (although it may be deleted from 
	 *							the server to make place for other items). 
     * @access public
	 * @return bool 			Returns TRUE on success or FALSE on failure. The getResultCode will return  
	 *							RES_NOTSTORED if the key already exists. 
     */
	public function add($key, $value, $expiration=0){
		if(isset($key) && isset($value)) {
			// test for known memcached memory limits
			if(strlen($this->prefix.$key)>250) 
				$this->_triggerError('Function add() requires $key with automatically added $prefix to be less than 250 bytes. Currently '.strlen($this->prefix.$key), E_USER_ERROR);
			
			else if(!is_array($value) && strlen($value)>1048576) 
				$this->_triggerError('Function add() requires $value to be less than 1048576 bytes. Currently '.strlen($value), E_USER_ERROR);
			
			else if(is_array($value) && strlen(serialize($array))>1048576) 
				$this->_triggerError('Function add() requires $value to be less than 1048576 bytes. Currently '.strlen(serialize($array)), E_USER_ERROR);
				
			if(!in_array($key, $this->keys)) $this->keys[] = $key;
			
			// passed limits, now try to add
			return $this->mcd->add($key, $value, $expiration);
		}
		else 
			$this->_triggerError('Function add() requires $key and $value. SAMPLE: $mcd->add(\'foo\', \'bar\');', E_USER_ERROR);
	}

   	/**
     * Add an item under a new key on a specific server. 
	 *
	 * Functionally equivalent to add, except that the free-form server_key can be used to map the key to a 
	 * specific server. This is useful if you need to keep a bunch of related keys on a certain server.  
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->addByKey('myserverkey', 'mykey1', $myarray1, 30);
	 * ?>
 	 * </code>
     *
     * @param string $server_key 	The key identifying the server to store the value on. 
	 * @param string $key 			The key under which to store the value.
	 * @param mixed $value 			The value to store. 
	 * @param int $expiration		The expiration time, defaults to 0. SEE: add() $expiration for more info. 
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *								RES_NOTSTORED if the key already exists. 
     */
	public function addByKey($server_key, $key, $value, $expiration=0){
		if(isset($server_key) && isset($key) && isset($value)) {

			if(strlen($this->prefix.$key)>250) 
				$this->_triggerError('Function addByKey() requires $key with automatically added $prefix to be less than 250 bytes. Currently '.strlen($this->prefix.$key), E_USER_ERROR);
			
			else if(!is_array($value) && strlen($value)>1048576) 
				$this->_triggerError('Function addByKey() requires $value to be less than 1048576 bytes. Currently '.strlen($value), E_USER_ERROR);
			
			else if(is_array($value) && strlen(serialize($array))>1048576) 
				$this->_triggerError('Function addByKey() requires $value to be less than 1048576 bytes. Currently '.strlen(serialize($array)), E_USER_ERROR);
				
			if(!in_array($key, $this->keys)) $this->keys[] = $key;
			
			$return = $this->mcd->addByKey($server_key, $key, $value, $expiration);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function addByKey() requires $server_key, $key and $value. SAMPLE: $mcd->addByKey(\'server_key\', \'foo\', \'bar\');', E_USER_ERROR);
	}

	/**
     * Add a server to the server pool. 
	 *
	 * Adds the specified server to the server pool. No connection is established to the server at this time, 
	 * but if you are using consistent key distribution option, some of the internal data structures will have 
	 * to be updated. Thus, if you need to add multiple servers, it is better to use addServers as the update 
	 * then happens only once. The same server may appear multiple times in the server pool, because no 
	 * ( duplication checks are made. This is not advisable; instead, use the weight option to increase the 
	 * selection weighting of this server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('mem1.domain.com', 11211, 33);
	 * ?>
 	 * </code>
     *
     * @param string $host 	The hostname of the memcache server. If the hostname is invalid, data-related 
	 *						operations will set RES_HOST_LOOKUP_FAILURE result code.  
	 * @param int $port 	The port on which memcache is running. Usually, this is 11211.
	 * @param int $weight 	The weight of the server relative to the total weight of all the servers in the pool. 
	 *						This controls the probability of the server being selected for operations. This is 
	 *						used only with consistent distribution option and usually corresponds to the amount 
	 *						of memory available to memcache on that server. 
     * @access public
	 * @return bool 		Returns TRUE on success or FALSE on failure. 
     */
	public function addServer($host, $port, $weight){
		if(isset($host) && isset($port) && isset($weight)) {
			$servers = $this->getServerList();
			
			if(!in_array(array('host'=>$host,'port'=>$port,'weight'=>$weight),$servers)) {
				$return = $this->mcd->addServer($host, $port, $weight);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('You are adding a server that already exists with this weight.');
		}
		else 
			$this->_triggerError('Function addServer() requires $host, $port and $weight. SAMPLE: $mcd->addServer(\'mem1.domain.com\', 11211, 33);', E_USER_ERROR);
	}

	/**
     * Add multiple servers to the server pool. 
	 *
	 * Adds servers  to the server pool. Each entry in servers is supposed to an array containing hostname, port, 
	 * and, optionally, weight of the server. No connection is established to the servers at this time. The same 
	 * server may appear multiple times in the server pool, because no duplication checks are made. This is not 
	 * advisable; instead, use the weight option to increase the selection weighting of this server. 
	 *
	 * <code>
	 * <?php
	 *	$mcd = new MemcacheManager();
	 * 	if(!count($mcd->getServerList())){
	 * 		$servers = array(
	 * 			array('localhost', 11211, 33),
	 * 			array('localhost', 11212, 33),
	 * 			array('localhost', 11213, 33)
	 * 		);
	 * 		$mcd->addServers($servers);
	 * 	}
	 * ?>
 	 * </code>
     *
     * @param array $servers 	Array of the servers to add to the pool. 
     * @access public
	 * @return bool 			Returns TRUE on success or FALSE on failure. 
     */
	public function addServers($servers){
		if(is_array($servers) && count($servers[0]==3)) {
			if(!count($this->getServerList())) {
				$return = $this->mcd->addServers($servers);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('Servers have already been added.');
		}
		else 
			$this->_triggerError('Function addServers() requires an array with each item containing $host, $port and $weight. SAMPLE: $mcd->addServers(array(array(\'mem1.domain.com\', 11211, 33),array(\'mem2.domain.com\', 11211, 67)));', E_USER_ERROR);
	}

	/**
     * Append data to an existing item. 
	 *
	 * Appends the given value  string to the value of an existing item. The reason that value is forced to be a 
	 * string is that appending mixed types is not well-defined. Note: If the OPT_COMPRESSION is enabled, the 
	 * operation will fail and a warning will be issued, because appending compressed data to a value that is 
	 * potentially already compressed is not possible. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->set('foo', 'abc');
	 * $mcd->append('foo', 'def');
	 * ?>
 	 * </code>
     *
     * @param string $key 	The key under which to store the value. 
	 * @param string $value	The string to append. 
     * @access public
	 * @return bool			Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *						RES_NOTSTORED if the key does not exist. 
     */
	public function append($key, $value){
		if(isset($key) && isset($value)) {
			if(is_string($this->get($key)) && is_string($value)) {
				
				if(strlen($this->get($key).$value)>1048576) 
					$this->_triggerError('Function append() requires new $value to be less than 1048576 bytes. Currently '.strlen($this->get($key).$value), E_USER_ERROR);
				
				$return = $this->mcd->append($key, $value);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('Function append() can only append a string to another string.');
		}
		else 
			$this->_triggerError('Function append() requires $key and $value. SAMPLE: $mcd->append(\'foo\', \'bar\');', E_USER_ERROR);
	}

	/**
     * Append data to an existing item on a specific server. 
	 *
	 * Functionally equivalent to append, except that the free-form server_key can be used to map the key to a 
	 * specific server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager('server_key');
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->setByKey('myserverkey', 'foo', 'abc');
	 * $mcd->appendByKey('myserverkey', 'foo', 'def');
	 * ?>
 	 * </code>
     *
	 * @param string $server_key	The key identifying the server to store the value on. 
     * @param string $key 			The key under which to store the value. 
	 * @param string $value 		The string to append. 
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *								RES_NOTSTORED if the key does not exist. 
     */
	public function appendByKey($server_key, $key, $value){
		if(isset($server_key) && isset($key) && isset($value)) {
			
			if(strlen($this->getByKey($server_key, $key).$value)>1048576) 
				$this->_triggerError('Function appendByKey() requires new $value to be less than 1048576 bytes. Currently '.strlen($this->getByKey($server_key, $key).$value), E_USER_ERROR);
			
			else if(is_string($this->getByKey($server_key, $key)) && is_string($value)) {
				$return = $this->mcd->appendByKey($server_key, $key, $value);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('Function appendByKey() can only append a string to another string.');
		}
		else 
			$this->_triggerError('Function appendByKey() requires $server_key, $key and $value. SAMPLE: $mcd->appendByKey(\'server_key\', \'foo\', \'bar\');', E_USER_ERROR);
	}
	
	/**
     * C.A.S. Compare and Swap an item. 
	 * 
	 * Performs a "check and set" operation, so that the item will be stored only if no other client has updated 
	 * it since it was last fetched by this client. The check is done via the cas_token parameter which is a 
	 * unique 64-bit value assigned to the existing item by memcache. See the documentation for get methods for 
	 * how to obtain this token. Note that the token is represented as a double due to the limitations of PHP's 
	 * integer space. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager('server_key');
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->setByKey('myserverkey', 'foo', 'abc');
	 * $mcd->appendByKey('myserverkey', 'foo', 'def');
	 * ?>
 	 * </code>
     *
	 * @param float $cas_token	Unique value associated with the existing item. Generated by memcache. 
     * @param string $key		The key under which to store the value.
	 * @param mixed $value		The value to store. 
	 * @param int $expire		The expiration time, defaults to 0. SEE: add() $expiration for more info.  
     * @access public
	 * @return bool				Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 * 							RES_DATA_EXISTS if the item you are trying to store has been modified since you 
	 *							last fetched it. 
     */
	public function cas($cas_token, $key, $value, $expiration=0){
		if(isset($cas_token) && isset($key) && isset($value)) {
			$return = $this->mcd->cas($cas_token, $key, $value, $expiration);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function cas() requires $cas_token, $key and $value. SAMPLE: $mcd->cas($cas_token, \'foo\', \'bar\');', E_USER_ERROR);
	}

	/**
     * C.A.S. Compare and Swap an item on a specific server. 
	 *
	 * Functionally equivalent to cas, except that the free-form server_key can be used to map the key to a 
	 * specific server. This is useful if you need to keep a bunch of related keys on a certain server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager('server_key');
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->setByKey('myserverkey', 'foo', 'abc');
	 * $mcd->appendByKey('myserverkey', 'foo', 'def');
	 * ?>
 	 * </code>
     *
	 * @param float $cas_token 		Unique value associated with the existing item. Generated by memcache. 
	 * @param string $server_key 	The key identifying the server to store the value on. 
     * @param string $key 			The key under which to store the value.
	 * @param mixed $value 			The value to store. 
	 * @param int $expire  			The expiration time, defaults to 0. SEE: add() $expiration for more info.
     * @access public
	 * @return bool 				Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *								RES_DATA_EXISTS if the item you are trying to store has been modified since 
	 *								you last fetched it. 
     */
	public function casByKey($cas_token, $server_key, $key, $value, $expiration=0){
		if(isset($cas_token) && isset($server_key) && isset($key) && isset($value)) {
			$return = $this->mcd->casByKey($cas_token, $server_key, $key, $value, $expiration);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function casByKey() requires $cas_token, $server_key, $key and $value. SAMPLE: $mcd->casByKey($cas_token, \'server_key\', \'foo\', \'bar\');', E_USER_ERROR);
	}

	/**
     * Decrement numeric item's value. 
	 *
	 * Decrements a numeric item's value by the specified offset. If the item's value is not numeric, it is 
	 * treated as if the value were 0. If the operation would decrease the value below 0, the new value will be 0. 
	 * decrement() will fail if the item does not exist. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->decrement('mykey1', 5);
	 * $mcd->decrement('mykey2');
	 * ?>
 	 * </code>
     *
     * @param string $key	Key of the item do decrement. 
	 * @param int $offset 	Decrement the item by value . Optional and defaults to 1.  
     * @access public
	 * @return mixed 		Returns item's new value on success or FALSE on failure. The getResultCode will 
	 *						return RES_NOTFOUND if the key does not exist. 
     */
	public function decrement($key, $offset=1){
		if(isset($key) && isset($offset)) {
			if(is_int($this->get($key)) && is_int($offset)) {
				$return = $this->mcd->decrement($key, $offset);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('Function decrement() can only decrement an interger from another interger.');
		}
		else 
			$this->_triggerError('Function decrement() requires $key and $offset. SAMPLE: $mcd->decrement(\'foo\', 1);', E_USER_ERROR);
	}

	/**
     * Delete an item. Deletes the key  from the server. 
	 *
	 * The time  parameter is the amount of time in seconds (or Unix time until which) the client wishes the 
	 * server to refuse add and replace commands for this key. For this amount of time, the item is put into a 
	 * delete queue, which means that it won't possible to retrieve it by the get command, but add and replace 
	 * command with this key will also fail (the set command will succeed, however). After the time passes, the 
	 * item is finally deleted from server memory. The parameter time defaults to 0 (which means that the item 
	 * will be deleted immediately and further storage commands with this key will succeed). 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->delete('mykey1', 30);
	 * $mcd->delete('mykey2');
	 * ?>
 	 * </code>
     *
     * @param string $key 	The key to be deleted.
	 * @param int $timeout 	The amount of time the server will wait to delete the item.
     * @access public
	 * @return bool 		Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *						RES_NOTFOUND if the key does not exist.
     */
	public function delete($key, $timeout=0){
		if($this->mcd->get($key)) {
			$return = $this->mcd->delete($key, $timeout);
			$this->_checkSuccess();
			return $return;
		}
	}
	
	/**
     * Delete an item from a specific server.  
	 *
	 * Functionally equivalent to delete, except that the free-form server_key can be used to map the key to a 
	 * specific server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->deleteByKey('server_key', 'mykey1', 30);
	 * $mcd->deleteByKey('server_key', 'mykey2');
	 * ?>
 	 * </code>
     *
	 * @param string $server_key	The key identifying the server to store the value on. 
     * @param string $key 			The key to be deleted.
	 * @param int $timeout 			The amount of time the server will wait to delete the item.
     * @access public
	 * @return bool 				Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *								RES_NOTFOUND if the key does not exist. 
     */
	public function deleteByKey($server_key, $key, $timeout=0){
		if(isset($server_key) && isset($key)) {
			if($this->mcd->getByKey($server_key, $key)) {
				$return = $this->mcd->deleteByKey($server_key, $key, $timeout);
				$this->_checkSuccess();
				return $return;
			}
		}
		else 
			$this->_triggerError('Function deleteByKey() requires $server_key and $key. SAMPLE: $mcd->deleteByKey(\'server_key\', \'foo\');', E_USER_ERROR);
	}

	/**
     * Fetch the next result. 
	 *
	 * Retrieves the next result from the last request. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211);
	 * 
	 * $mcd->set('int', 99);
	 * $mcd->set('string', 'a simple string');
	 * $mcd->set('array', array(11, 12));
	 * 
	 * $mcd->getDelayed(array('int', 'array'), true);
	 * while ($result = $mcd->fetch()) {
	 *     var_dump($result);
	 * }
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return mixed 	Returns the next result or FALSE otherwise. The getResultCode will return RES_END if 
	 *					result set is exhausted. 
     */
	public function fetch(){
		$return = $this->mcd->fetch();
		$this->_checkSuccess();
		return $return;
	}
	
	/**
     * Fetch the next result. 
	 *
	 * Retrieves the next result from the last request. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211);
	 * 
	 * $mcd->set('int', 99);
	 * $mcd->set('string', 'a simple string');
	 * $mcd->set('array', array(11, 12));
	 * 
	 * $mcd->getDelayed(array('int', 'array'), true);
	 * var_dump($mcd->fetchAll());
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return mixed 	Returns the results or FALSE on failure. Use getResultCode if necessary. 
     */
	public function fetchAll(){
		$return = $this->mcd->fetchAll();
		$this->_checkSuccess();
		return $return;
	}

	/**
     * Invalidate all items in the cache. 
	 *
	 * Invalidates all existing cache items immediately (by default) or after the delay specified. After 
	 * invalidation none of the items will be returned in response to a retrieval command (unless it's stored 
	 * again under the same key after Memcached::flush() has invalidated the items). The flush does not actually 
	 * free all the memory taken up by the existing items; that will happen gradually as new items are stored. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->flushmc(10);
	 * ?>
 	 * </code>
     *
	 * @param int $delay 	Numer of seconds to wait before invalidating the items. 
     * @access public
	 * @return bool 		Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function flushmc($delay=0){
		if(is_int($delay)){
			$return = $this->mcd->flush($delay);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function flushmc() requires $delay to be an interger. SAMPLE: $mcd->flushmc(30);', E_USER_ERROR);
	}

   	/**
     * Retrieve an item. 
	 *
	 * Returns the item that was previously stored under the key. If the item is found and cas_token variable is 
	 * provided, it will contain the CAS token value for the item. See cas for how to use CAS tokens. Read-through 
	 * caching callback may be specified via cache_cb parameter. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * echo $mcd->get('mykey1', NULL, NULL);
	 * ?>
 	 * </code>
     *
     * @param string $key 			The key of the item to retrieve. 
	 * @param callback $cache_cb	Read-through caching callback or NULL. This callback handler will only get executed 
	 *								if the $key DOES NOT exist.
	 * @param float $cas_token		The variable to store the CAS token in. 
     * @access public
	 * @return mixed				Returns the value stored in the cache or FALSE otherwise. The getResultCode will 
	 *								return RES_NOTFOUND if the key does not exist.
     */
	public function get($key, $cache_cb=NULL, &$cas_token=NULL){
		if($cache_cb==NULL) $cache_cb = array($this, 'getFailCallback');
		$return = $this->mcd->get($key, $cache_cb, $cas_token);
		$this->_checkSuccess();
		return $return;
	}

	/**
     * Retrieve an item from a specific server. 
	 *
	 * Functionally equivalent to get, except that the free-form server_key can be used to map the key to a 
	 * specific server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * echo $mcd->getByKey('server_key', 'mykey1');
	 * ?>
 	 * </code>
     *
	 * @param string $server_key	The key identifying the server to store the value on. 
     * @param string $key 			The key of the item to retrieve. 
	 * @param callback $cache_cb 	Read-through caching callback or NULL. 
	 * @param float $cas_token		The variable to store the CAS token in. 
     * @access public
	 * @return mixed				Returns the value stored in the cache or FALSE otherwise. The getResultCode will 
	 *								return RES_NOTFOUND if the key does not exist. 
     */
	public function getByKey($server_key, $key, $cache_cb=NULL, &$cas_token=NULL){
		$return = $this->mcd->getByKey($server_key, $key, $cache_cb, $cas_token);
		$this->_checkSuccess();
		return $return;
	}
	
	/**
     * Retrieve an item from a specific server.  
	 *
	 * Functionally equivalent to get, except that the free-form server_key can be used to map the key to a 
	 * specific server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->set('int', 99);
	 * $mcd->set('string', 'a simple string');
	 * $mcd->set('array', array(11, 12));
	 * echo $mcd->getDelayed(array('int', 'string', 'array'), TRUE, NULL);
	 * ?>
 	 * </code>
     *
     * @param array $keys 			Array of keys to request.  
	 * @param bool $with_cas 		Whether to request CAS token values also.  
	 * @param callback $value_cb	The result callback or NULL.  
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function getDelayed($keys, $with_cas=TRUE, $value_cb=NULL){
		$return = $this->mcd->getDelayed($keys, $with_cas, $value_cb);
		$this->_checkSuccess();
		return $return;
	}
	
	/**
     * Request multiple items from a specific server  
	 *
	 * Functionally equivalent to getDelayed, except that the free-form server_key can be used to map the key to 
	 * a specific server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->set('int', 99);
	 * $mcd->set('string', 'a simple string');
	 * $mcd->set('array', array(11, 12));
	 * echo $mcd->getDelayedByKey('server_key', array('int', 'string', 'array'), TRUE, NULL);
	 * ?>
 	 * </code>
     *
	 * @param string $server_key 	The key identifying the server to store the value on. 
     * @param array $keys 			Array of keys to request. 
	 * @param bool $with_cas 		Whether to request CAS token values also.
	 * @param callback $value_cb	The result callback or NULL. 
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function getDelayedByKey($server_key, $keys, $with_cas=TRUE, $value_cb=NULL){
		$return = $this->mcd->getDelayed($server_key, $keys, $with_cas, $value_cb);
		$this->_checkSuccess();
		return $return;
	}
	
	/**
     * Retrieve multiple items
	 *
	 * Similar to get, but instead of a single key item, it retrievess multiple items the keys of which are 
	 * specified in the keys  array. If cas_tokens variable is provided, it is filled with the CAS token values 
	 * for the found items. The flags  parameter can be used to specify additional options for getMulti(). 
	 * Currently, the only available option is Memcached::GET_PRESERVE_ORDER that ensures that the keys are 
	 * returned in the same order as they were requested in. NOTE: Unlike get it is not possible to specify a 
	 * read-through cache callback for getMulti(), because the memcache protocol does not provide information on 
	 * which keys were not found in the multi-key request.  
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $items = array(
	 *     'key1' => 'value1',
	 *     'key2' => 'value2',
	 *     'key3' => 'value3'
	 * );
	 * $mcd->setMulti($items);
	 * $result = $mcd->getMulti(array('key1', 'key3', 'badkey'), $cas);
	 * var_dump($result, $cas);
	 * ?>
 	 * </code>
     *
     * @param array $keys  			Array of keys to retrieve. 
	 * @param array $cas_tokens		The variable to store the CAS tokens for the found items. 
	 * @param int $flags 			The flags for the get operation. 
     * @access public
	 * @return mixed				Returns the array of found items or FALSE on failure. Use getResultCode 
	 *								if necessary. 
     */
	public function getMulti($keys, &$cas_tokens=NULL, $flags=NULL){
		if(is_array($keys)){
			$return = $this->mcd->getMulti($keys, $cas_tokens, $flags);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function getMulti() requires $keys. SAMPLE: $mcd->getMulti(array(\'key1\', \'key3\', \'badkey\'), $cas);', E_USER_ERROR);
	}
	
	/**
     * Retrieve multiple items from a specific server
	 *
	 * functionally equivalent to getMulti, except that the free-form server_key can be used to map the keys to a 
	 * specific server.   
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $items = array(
	 *     'key1' => 'value1',
	 *     'key2' => 'value2',
	 *     'key3' => 'value3'
	 * );
	 * $mcd->setMulti($items);
	 * $result = $mcd->getMultiByKey('server_key', array('key1', 'key3', 'badkey'), $cas);
	 * var_dump($result, $cas);
	 * ?>
 	 * </code>
     *
     * @param string $server_key	The key identifying the server to store the value on. 
	 * @param array $keys  			Array of keys to retrieve. 
	 * @param string $cas_tokens	The variable to store the CAS tokens for the found items. 
	 * @param int $flags 			The flags for the get operation. 
     * @access public
	 * @return mixed				Returns the array of found items or FALSE on failure. Use getResultCode 
	 *								if necessary. 
     */
	public function getMultiByKey($server_key, $keys, &$cas_tokens=NULL, $flags=NULL){
		if(isset($server_key) && is_array($keys)){
			$return = $this->mcd->getMultiByKey($server_key, $keys, $cas_tokens, $flags);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function getMultiByKey() requires $server_key and $keys. SAMPLE: $mcd->getMultiByKey(\'server_key\', array(\'key1\', \'key3\', \'badkey\'), $cas);', E_USER_ERROR);
	}
	
	/**
     * Retrieve a Memcached option value
	 *
	 * This method returns the value of a Memcached option. Some options correspond to the ones defined by 
	 * libmemcached, and some are specific to the extension. See Memcached Constants for more information. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * var_dump($mcd->getOption(Memcached::OPT_COMPRESSION));
	 * ?>
 	 * </code>
     *
     * @param int $option	One of the Memcached::OPT_* constants. 
	 *						SEE: http://www.php.net/manual/en/memcached.constants.php
     * @access public
	 * @return mixed		Returns the value of the requested option, or FALSE on error. 
     */
	public function getOption($option){
		if(isset($option)){
			$return = $this->mcd->getOption($option);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function getOption() requires $option. SAMPLE: $mcd->getOption(Memcached::OPT_COMPRESSION);', E_USER_ERROR);
	}
	
	/**
     * Return the result code of the last operation
	 *
	 * Returns one of the Memcached::RES_* constants that is the result of the last executed Memcached method.  
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->add('foo', 'bar');
	 * if ($mcd->getResultCode() == Memcached::RES_NOTSTORED) {
	 *     // my code
	 * }
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return int	Result code of the last Memcached operation. 
     */
	public function getResultCode(){
		return $this->mcd->getResultCode();
	}
	
	/**
     * Return the message describing the result of the last operation
	 *
	 * Returns a string that describes the result code of the last executed Memcached method. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->add('foo', 'bar'); // first time should succeed
	 * $mcd->add('foo', 'bar');
	 * echo $mcd->getResultMessage(),"\n";
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return string 	Message describing the result of the last Memcached operation.
     */
	public function getResultMessage(){
		return $this->mcd->getResultMessage();
	}
	
	/**
     * Map a key to a server
	 *
	 * Returns the server that would be selected by a particular server_key in all the *ByKey() operations. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServers(array(
	 *     array('mem1.domain.com', 11211, 40),
	 *     array('mem2.domain.com', 11211, 40),
	 *     array('mem3.domain.com', 11211, 20),
	 * ));
	 * 
	 * $mcd->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
	 * 
	 * var_dump($mcd->getServerByKey('user'));
	 * var_dump($mcd->getServerByKey('log'));
	 * var_dump($mcd->getServerByKey('ip'));
	 * ?>
 	 * </code>
     *
	 * @param string $server_key	The key identifying the server to store the value on. 
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function getServerByKey($server_key){
		if(isset($server_key)) {
			$return = $this->mcd->getServerByKey();
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function getServerByKey() requires $server_key. SAMPLE: $mcd->getServerByKey(\'server_key\');', E_USER_ERROR);
	}
	
	/**
     * Get the list of the servers in the pool
	 *
	 * Returns the list of all servers that are in its server pool. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('mem1.domain.com', 11211, 33);
	 * $mcd->addServer('mem1.domain.com', 11211, 67);
	 * print_r($mcd->getServerList());
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return array Returns the list of all servers in the server pool.  
     */
	public function getServerList(){
		return $this->mcd->getServerList();
	}	
	
	/**
     * Get server pool statistics
	 *
	 * Returns an array containing the state of all available memcache servers. See memcache protocol 
	 * specification for details on these statistics. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * print_r($mcd->getStats());
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return array	Array of server statistics, one entry per server. 
     */
	public function getStats(){
		return $this->mcd->getStats();
	}
	
	/**
     * Get server pool version info
	 *
	 * Returns an array containing the version info for all available memcache servers. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * print_r($mcd->getVersion());
	 * ?>
 	 * </code>
     *
     * @access public
	 * @return array	Array of server versions, one entry per server. 
     */
	public function getVersion(){
		return $this->mcd->getVersion();
	}

	/**
     * Increment item's value
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->increment('mykey1', 5);
	 * $mcd->increment('mykey2');
	 * ?>
 	 * </code>
     *
     * @param string $key	Key of the item to increment 
	 * @param int $offset	Increment the item by value . Optional and defaults to 1. 
     * @access public
	 * @return mixed 		Returns new item's value on success or FALSE on failure. The 
	 *						getResultCode will return RES_NOTFOUND if the key does not exist. 
     */
	public function increment($key, $offset=1){
		if(isset($key) && isset($offset)) {
			if(is_int($this->get($key)) && is_int($offset)) {
				$return = $this->mcd->increment($key, $offset);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('Function increment() can only increment an interger from another interger.');
		}
		else 
			$this->_triggerError('Function increment() requires $key and $offset. SAMPLE: $mcd->increment(\'foo\', 1);', E_USER_ERROR);
	}

	/**
     * Prepend data to an existing item
	 *
	 * prepends the given value  string to the value of an existing item. The reason that value is forced to be 
	 * a string is that prepending mixed types is not well-defined.  Note: If the OPT_COMPRESSION is enabled, 
	 * the  operation will fail and a warning will be issued, because appending compressed data to a value that
	 * is potentially already compressed is not possible. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->set('foo', 'abc');
	 * $mcd->prepend('foo', 'def');
	 * ?>
 	 * </code>
     *
     * @param string $key 	The key of the item to prepend the data to. 
	 * @param string $value	The string to prepend. 
     * @access public
	 * @return bool			Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *						RES_NOTSTORED if the key does not exist. 
     */
	public function prepend($key, $value){
		if(isset($key) && isset($value)) {
			if(is_string($this->get($key)) && is_string($value)) {
				
				if(strlen($this->get($key).$value)>1048576) 
					$this->_triggerError('Function prepend() requires new $value to be less than 1048576 bytes. Currently '.strlen($this->get($key).$value), E_USER_ERROR);
				
				$return = $this->mcd->prepend($key, $value);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('Function prepend() can only prepend a string to another string.');
		}
		else 
			$this->_triggerError('Function prepend() requires $key and $value. SAMPLE: $mcd->prepend(\'foo\', \'bar\');', E_USER_ERROR);
	}

	/**
     * Prepend data to an existing item on a specific server
	 *
	 * Functionally equivalent to prepend, except that the free-form server_key can be used to map the key to a 
	 * specific server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager('server_key');
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->setByKey('myserverkey', 'foo', 'abc');
	 * $mcd->prependByKey('myserverkey', 'foo', 'def');
	 * ?>
 	 * </code>
     *
	 * @param string $server_key	The key identifying the server to store the value on. 
     * @param string $key 			The key of the item to prepend the data to. 
	 * @param string $value 		The string to prepend. 
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *								RES_NOTSTORED if the key does not exist. 
     */
	public function prependByKey($server_key, $key, $value){
		if(isset($server_key) && isset($key) && isset($value)) {
			
			if(strlen($this->getByKey($server_key, $key).$value)>1048576) 
				$this->_triggerError('Function prependByKey() requires new $value to be less than 1048576 bytes. Currently '.strlen($this->getByKey($server_key, $key).$value), E_USER_ERROR);
			
			else if(is_string($this->getByKey($server_key, $key)) && is_string($value)) {
				$return = $this->mcd->prependByKey($server_key, $key, $value);
				$this->_checkSuccess();
				return $return;
			}
			else 
				$this->_triggerError('Function prependByKey() can only append a string to another string.');
		}
		else 
			$this->_triggerError('Function prependByKey() requires $server_key, $key and $value. SAMPLE: $mcd->prependByKey(\'server_key\', \'foo\', \'bar\');', E_USER_ERROR);
	}

   	/**
     * Replace the item under an existing key. 
	 *
	 * Similar to set, but the operation fails if the key does not exist on the server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->replace('mykey1', $myarray1, 30);
	 * ?>
 	 * </code>
     *
     * @param string $key 	The key under which to store the value.
	 * @param mixed $value 	The value to store. 
	 * @param int $expire  	The expiration time, defaults to 0. SEE: add() $expiration for more info. 
     * @access public
	 * @return bool			Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *						RES_NOTSTORED if the key does not exist. 
     */
	public function replace($key, $value, $expire=0){
		if(isset($key) && isset($value)){
			if($this->mcd->get($key)){
				$return = $this->mcd->replace($key, $value, $expire);
				$this->_checkSuccess();
				return $return;
			}
		}
		else 
			$this->_triggerError('Function replace() requires $key and $value. SAMPLE: $mcd->replace(\'mykey1\', $myarray1, 30);', E_USER_ERROR);
	}
	
	/**
     * Replace the item under an existing key on a specific server
	 *
	 * Functionally equivalent to replace, except that the free-form server_key can be used to map the key to a 
	 * specific server. This is useful if you need to keep a bunch of related keys on a certain server. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->replaceByKey('server_key', 'mykey1', $myarray1, 30);
	 * ?>
 	 * </code>
     *
	 * @param string $server_key 	The key under which to store the value.
     * @param string $key 			The key under which to store the value.
	 * @param mixed $value 			The value to store. 
	 * @param int $expire  			The expiration time, defaults to 0. SEE: add() $expiration for more info. 
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. The getResultCode will return 
	 *								RES_NOTSTORED if the key does not exist. 
     */
	public function replaceByKey($server_key, $key, $value, $expire=0){
		if(isset($server_key) && isset($key) && isset($value)){
			if($this->mcd->getByKey($server_key, $key)){
				$return = $this->mcd->replaceByKey($server_key, $key, $value, $expire);
				$this->_checkSuccess();
				return $return;
			}
		}
		else 
			$this->_triggerError('Function replaceByKey() requires $server_key, $key and $value. SAMPLE: $mcd->replaceByKey(\'server_key\', \'mykey1\', $myarray1, 30);', E_USER_ERROR);
	}

   	/**
     * Store an item. 
	 *
	 * Stores the value  on a memcache server under the specified key. The expiration  parameter can be used to 
	 * control when the value is considered expired. The value can be any valid PHP type except for resources, 
	 * because those cannot be represented in a serialized form. If the OPT_COMPRESSION option is turned on, the 
	 * serialized value will also be compressed before storage. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->set('mykey1', $myarray1, 30);
	 * ?>
 	 * </code>
     *
     * @param string $key 	The key under which to store the value.
	 * @param mixed $value 	The value to store. 
	 * @param int $expire  	The expiration time, defaults to 0. SEE: add() $expiration for more info. 
     * @access public
	 * @return bool			Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function set($key, $value, $expire=0){
		if(isset($key) && isset($value)){
			if(strlen($this->prefix.$key)>250) $this->_triggerError('Function set() requires $key with automatically added PREFIX to be less than 250 bytes. Currently '.strlen($this->prefix.$key), E_USER_ERROR);
			else if(!is_array($value) && strlen($value)>1048576) $this->_triggerError('Function set() requires $value to be less than 1048576 bytes. Currently '.strlen($value), E_USER_ERROR);
			else if(is_array($value) && strlen(serialize($array))>1048576) $this->_triggerError('Function set() requires $value to be less than 1048576 bytes. Currently '.strlen(serialize($array)), E_USER_ERROR);
			
			$return = $this->mcd->set($key, $value, $expire);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function set() requires $key and $value. SAMPLE: $mcd->set(\'mykey1\', $myarray1, 30);', E_USER_ERROR);
	}

	/**
     * Store an item. 
	 *
	 * Stores the value  on a memcache server under the specified key. The expiration  parameter can be used to 
	 * control when the value is considered expired. The value can be any valid PHP type except for resources, 
	 * because those cannot be represented in a serialized form. If the OPT_COMPRESSION option is turned on, the 
	 * serialized value will also be compressed before storage. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $mcd->setByKey('server_key', 'mykey1', $myarray1, 30);
	 * ?>
 	 * </code>
     *
     * @param string $key 	The key under which to store the value.
	 * @param mixed $value 	The value to store. 
	 * @param int $expire  	The expiration time, defaults to 0. SEE: add() $expiration for more info. 
     * @access public
	 * @return bool			Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function setByKey($server_key, $key, $value, $expire=0){
		if(isset($server_key) && isset($key) && isset($value)){
			if(strlen($this->prefix.$key)>250) $this->_triggerError('Function setByKey() requires $key with automatically added PREFIX to be less than 250 bytes. Currently '.strlen($this->prefix.$key), E_USER_ERROR);
			else if(!is_array($value) && strlen($value)>1048576) $this->_triggerError('Function setByKey() requires $value to be less than 1048576 bytes. Currently '.strlen($value), E_USER_ERROR);
			else if(is_array($value) && strlen(serialize($array))>1048576) $this->_triggerError('Function setByKey() requires $value to be less than 1048576 bytes. Currently '.strlen(serialize($array)), E_USER_ERROR);
			
			$return = $this->mcd->setByKey($server_key, $key, $value, $expire);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function setByKey() requires $server_key, $key and $value. SAMPLE: $mcd->setByKey(\'server_key\', \'mykey1\', $myarray1, 30);', E_USER_ERROR);
	}

	/**
     * Store multiple items
	 *
	 * Similar to set, but instead of a single key/value item, it works on multiple items specified in items. 
	 * The expiration time applies to all the items at once. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $items = array(
	 *     'key1' => 'value1',
	 *     'key2' => 'value2',
	 *     'key3' => 'value3'
	 * );
	 * $mcd->setMulti($items, 30);
	 * ?>
 	 * </code>
     *
     * @param array $items		An array of key/value pairs to store on the server. 
	 * @param int $expiration	The expiration time, defaults to 0. SEE: add() $expiration for more info.
     * @access public
	 * @return bool				Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function setMulti($items, $expiration=0){
		if(is_array($items)){
			$return = $this->mcd->setMulti($items, $expiration);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function setMulti() requires $items to be an array. SAMPLE: $mcd->setMulti(array(\'key1\'=>\'value1\',\'key2\'=>\'value2\',\'key3\'=>\'value3\'), 30);', E_USER_ERROR);
	}
	
	/**
     * Store multiple items
	 *
	 * Similar to set, but instead of a single key/value item, it works on multiple items specified in items. 
	 * The expiration time applies to all the items at once. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->addServer('localhost', 11211, 33);
	 * $items = array(
	 *     'key1' => 'value1',
	 *     'key2' => 'value2',
	 *     'key3' => 'value3'
	 * );
	 * $mcd->setMultiByKey('server_key', $items, 30);
	 * ?>
 	 * </code>
     *
	 * @param string $server_key	The key identifying the server to store the value on. 
     * @param array $items			An array of key/value pairs to store on the server. 
	 * @param int $expiration		The expiration time, defaults to 0. SEE: add() $expiration for more info.
     * @access public
	 * @return bool					Returns TRUE on success or FALSE on failure. Use getResultCode if necessary. 
     */
	public function setMultiByKey($server_key, $items, $expiration=0){
		if(isset($server_key) && is_array($items)){
			$return = $this->mcd->setMultiByKey($server_key, $items, $expiration);
			$this->_checkSuccess();
			return $return;
		}
		else 
			$this->_triggerError('Function setMulti() requires $server_key and $items. SAMPLE: $mcd->setMultiByKey(\'server_key\', array(\'key1\'=>\'value1\',\'key2\'=>\'value2\',\'key3\'=>\'value3\'), 30);', E_USER_ERROR);
	}
	
	/**
     * Set a Memcached option
	 *
	 * This method sets the value of a Memcached option. Some options correspond to the ones defined by 
	 * libmemcached, and some are specific to the extension. See Memcached Constants for more information. 
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->setOption(Memcached::OPT_HASH, Memcached::HASH_MURMUR);
	 * $mcd->setOption(Memcached::OPT_PREFIX_KEY, "widgets");
	 * ?>
 	 * </code>
     *
	 * @param int $option	Memcached Option
     * @param mixed $value	Memcached Option Value
     * @access public
	 * @return bool			Returns TRUE on success or FALSE on failure. 
     */
	public function setOption($option, $value){
		if(isset($option) && isset($value))
			return $this->mcd->setOption($option, $value);
		else 
			$this->_triggerError('Function setOption() requires $option and $value. SAMPLE: $mcd->setOption(Memcached::OPT_HASH, Memcached::HASH_MURMUR);', E_USER_ERROR);
	}

	/**
     * Add Tags to a Key.
	 *
	 * For best results, you should namespace your tags. Start all your tags with something consistent, like:
	 * 'tag_', and then you can keep that patters going with tags like 'tag_user_', or 'tag_object_' etc.
	 * This will allow you to find things the easiest by _searchTags() to find all tags in that namespace.
	 * So tags like 'tag_user_peter', 'tag_user_janet', 'tag_user_john' can be searched with
	 * _searchTags('_user_') and all your users will be found.
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->set('foo','bar');
	 * $mcd->addKeyTags('foo', array('tag1', 'tag2', 'tag3'));
	 * $mcd->addKeyTags('foo', 'tag4');
	 * ?>
 	 * </code>
     *
     * @param string $key	Memcached Key
	 * @param string $tags	Memcached Tag
     * @access public
     */
	public function addKeyTags($key, $tags){
		if(isset($this->tagdb)){
			if(!empty($key) && is_string($key) && isset($tags)){
				if($this->get($key)){
					$servers = mysql_real_escape_string(serialize($this->getServerList()));
					$key = mysql_real_escape_string($key);
					
					$sql = "INSERT IGNORE INTO `tags` (`key`,`tag`,`servers`) VALUES ";
					switch(true){
						case(is_string($tags)):
							$tag = mysql_real_escape_string($tags);
							$sql .= "('{$key}', '{$tag}', MD5('{$servers}'));";
							break;
							
						case(is_array($tags)):
							foreach($tags as $tag){
								$tag = mysql_real_escape_string($tag);
								$sql .= "('{$key}', '{$tag}', MD5('{$servers}')),";
							}
							$sql = rtrim($sql,',').";";
							break;
					}
					mysql_query($sql) or $this->_triggerError(mysql_error());
				}
			}
			else $this->_triggerError('Function addKeyTags() requires $tag to be string and $keys to be array.');
		}
		else $this->_triggerError('Function addKeyTags() unable to connect to tag database.');
	}
	
	/**
     * Add Tag to a Multiple Keys.
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->set('foo','value1');
	 * $mcd->set('bar','value2');
	 * $mcd->addMultiKeyTag(array('foo', 'bar'), 'tag_info');
	 * ?>
 	 * </code>
     *
     * @param array $keys	Memcached Keys
	 * @param string $tag	Memcached Tag
     * @access public
     */
	public function addMultiKeyTag($keys, $tag){
		if(isset($this->tagdb)){
			if(!empty($tag) && is_string($tag) && is_array($keys)){
				$servers = mysql_real_escape_string(serialize($this->getServerList()));
				$tag = mysql_real_escape_string($tag);
				
				$sql = "INSERT IGNORE INTO `tags` (`key`,`tag`,`servers`) VALUES ";
				foreach($keys as $key){
					if($this->get($key)){
						$key = mysql_real_escape_string($key);
						$sql .= "('{$key}', '{$tag}', MD5('{$servers}')),";
					}
				}
				$sql = rtrim($sql,',').";";
				mysql_query($sql) or $this->_triggerError(mysql_error());
			}
			else $this->_triggerError('Function addMultiKeyTag() requires $tag to be string and $keys to be array.');
		}
		else $this->_triggerError('Function addMultiKeyTag() unable to connect to tag database.');
	}
	
	/**
     * Delete Tags from a Key
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->set('foo','bar');
	 * $mcd->addKeyTags('foo', array('tag1', 'tag2', 'tag3'));
	 * $mcd->deleteKeyTags('foo', array('tag1', 'tag3'));
	 * $mcd->deleteKeyTags('foo', 'tag2');
	 * ?>
 	 * </code>
     *
     * @param string $key	Memcached Key
	 * @param string $tags	Memcached Tag
     * @access public
     */
	public function deleteKeyTags($key, $tags){
		if(isset($this->tagdb)){
			if(!empty($key) && is_string($key) && isset($tags)){
				$servers = mysql_real_escape_string(serialize($this->getServerList()));
				$key = mysql_real_escape_string($key);
				switch(true){
					case(is_string($tags)):
						$tag = mysql_real_escape_string($tags);
						$sqltags = "'{$tag}'";
						break;
						
					case(is_array($tags)):
						$sqltags = '';
						foreach($tags as $tag){
							$tag = mysql_real_escape_string($tag);
							$sqltags .= "'{$tag}',";
						}
						$sqltags = rtrim($sqltags,',');
						break;
				}
				$sql = "DELETE FROM `tags` WHERE `key`='{$key}' AND `tag` IN ({$sqltags}) AND `servers`=MD5('{$servers}');";
				mysql_query($sql) or $this->_triggerError(mysql_error());
			}
			else $this->_triggerError('Function deleteKeyTags() requires $key to be string and $tags to be array.');
		}
		else $this->_triggerError('Function deleteKeyTags() unable to connect to tag database.');
	}
	
	/**
     * Delete Tag from Multiple Keys.
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->set('foo','value1');
	 * $mcd->set('bar','value2');
	 * $mcd->addKeyTags('bar', array('tag_user_peter', 'tag_user_janet', 'tag_user_john'));
	 * $mcd->addKeyTags('foo', array('tag_user_peter', 'tag_user_janet', 'tag_user_john'));
	 * $mcd->deleteMultiKeyTag(array('foo', 'bar'), 'tag_user_john');
	 * ?>
 	 * </code>
     *
     * @param array $keys	Memcached Keys
	 * @param string $tag	Memcached Tag
     * @access public
     */
	public function deleteMultiKeyTag($keys, $tag){
		if(isset($this->tagdb)){
			if(!empty($tag) && is_string($tag) && is_array($keys)){
				$servers = mysql_real_escape_string(serialize($this->getServerList()));
				$tag = mysql_real_escape_string($tag);
				
				$sqlkeys = '';
				foreach($keys as $key){
					$key = mysql_real_escape_string($key);
					$sqlkeys .= "'{$key}',";
				}
				$sqlkeys = rtrim($sqlkeys,',');
				$sql = "DELETE FROM `tags` WHERE `tag`='{$tag}' AND `key` IN ({$sqlkeys}) AND `servers`=MD5('{$servers}');";
				mysql_query($sql) or $this->_triggerError(mysql_error());
			}
			else $this->_triggerError('Function deleteMultiKeyTag() requires $tag to be string and $keys to be array.');
		}
		else $this->_triggerError('Function deleteMultiKeyTag() unable to connect to tag database.');
	}
	
	/**
     * Fetch Tags associated with a Key
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->set('foo','bar');
	 * $mcd->addKeyTags('foo', array('tag1', 'tag2', 'tag3'));
	 * $mcd->addKeyTags('foo', 'tag4');
	 * $mcd->deleteKeyTags('foo', array('tag1', 'tag3'));
	 * print_r($mcd->fetchKeyTags('foo'));
	 * ?>
 	 * </code>
     *
     * @param string $key	Memcached Key
	 * @param string $tags	Memcached Tag
     * @access public
     */
	public function fetchKeyTags($key){
		if(isset($this->tagdb)){
			if(!empty($key) && is_string($key)){
				$servers = mysql_real_escape_string(serialize($this->getServerList()));
				$key = mysql_real_escape_string($key);
				$sql = "SELECT `tag` FROM `tags` WHERE `key`='{$key}' AND `servers`=MD5('{$servers}')";
				$result = mysql_query($sql) or $this->_triggerError(mysql_error());
				$return = array();
				while($row=mysql_fetch_array($result)) $return[] = $row['tag'];
				return $return;
			}
			else $this->_triggerError('Function fetchKeyTag() requires $key to be string.');
		}
		else $this->_triggerError('Function fetchKeyTag() unable to connect to tag database.');
	}
	
	/**
     * Search for Namespaced Tag
	 *
	 * <code>
	 * <?php
	 * $mcd = new MemcacheManager();
	 * $mcd->set('foo','bar');
	 * $mcd->addKeyTags('foo', array('tag_user_peter', 'tag_user_janet', 'tag_user_john'));
	 * print_r($mcd->searchTag('_user_'));
	 * ?>
 	 * </code>
     *
     * @param string $tag	Namespaced Tag Search
     * @access public
     */
	public function searchForKeys($tag){
		if(isset($this->tagdb)){
			if(!empty($tag) && is_string($tag)){
				$servers = mysql_real_escape_string(serialize($this->getServerList()));
				$tag = mysql_real_escape_string($tag);
				$sql = "SELECT DISTINCT `key` FROM `tags` WHERE `tag` LIKE '%{$tag}%' AND `servers`=MD5('{$servers}')";
				$result = mysql_query($sql) or $this->_triggerError(mysql_error());
				$return = array();
				while($row=mysql_fetch_array($result)) {
					$keyTags = $this->fetchKeyTags($row['key']);
					$return[] = array('key'=>$row['key'],'tags'=>$keyTags);
				}
				return $return;
			}
			else $this->_triggerError('Function searchForKeys() requires $tag to be string.');
		}
		else $this->_triggerError('Function searchForKeys() unable to connect to tag database.');
	}
	
	/**
     * Delete Keys and then any tags that might be saved
	 *
	 * <code>
	 * <?php
	 * $mcd->set('foo','value1');
	 * $mcd->set('bar','value2');
	 * $mcd->addKeyTags('bar', array('tag_user_peter', 'tag_user_janet', 'tag_user_john'));
	 * $mcd->addKeyTags('foo', array('tag_user_peter', 'tag_user_janet', 'tag_user_john'));
	 * 
	 * $keys = $mcd->searchForKeys('_user_');
	 * $mcd->cleanDelete($keys);
	 * ?>
 	 * </code>
     *
     * @param array $array	Keys and Tags to delete from database and memcache
     * @access public
     */
	public function cleanDelete($array){
		if(!empty($array) && is_array($array)){
			foreach($array as $item){
				$this->delete($item['key']);
				$this->deleteKeyTags($item['key'], $item['tags']);
			}
		}
		else $this->_triggerError('Function searchForKeys() requires $tag to be string.');
	}
	
	/**
     * Check success of execution to Memcached
	 *
	 * Included with each class method. Returns a detailed string that describes the result code of the last 
	 * executed Memcached method. Triggers an error if getResultCode() != 0,21
     *
     * @access private
     */
	private function _checkSuccess(){
		$code = $this->getResultCode();
		switch($code){
			case 0: // Memcached::RES_SUCCESS
				$result = "The operation was successful.";
				break;
				
			case 1: // Memcached::RES_FAILURE
				$result = "The operation failed in some fashion.";
				break;
				
			case 2: // Memcached::RES_HOST_LOOKUP_FAILURE
				$result = "DNS lookup failed.";
				break;
				
			case 7: // Memcached::RES_UNKNOWN_READ_FAILURE
				$result = "Failed to read network data.";
				break;
				
			case 8: // Memcached::RES_PROTOCOL_ERROR
				$result = "Bad command in memcached protocol.";
				break;
				
			case 9: // Memcached::RES_CLIENT_ERROR
				$result = "Error on the client side.";
				break;
				
			case 10: // Memcached::RES_SERVER_ERROR
				$result = "Error on the server side.";
				break;
				
			case 5: // Memcached::RES_WRITE_FAILURE
				$result = "Failed to write network data.";
				break;
				
			case 12: // Memcached::RES_DATA_EXISTS
				$result = "Failed to do compare-and-swap: item you are trying to store has been modified since you last fetched it.";
				break;
				
			case 14: // Memcached::RES_NOTSTORED
				$result = "Item was not stored: but not because of an error. This normally means that either the condition for an \"add\" or a \"replace\" command wasn't met, or that the item is in a delete queue.";
				break;
				
			case 16: // Memcached::RES_NOTFOUND
				$result = "Item with this key was not found (with \"get\" operation or \"cas\" operations).";
				break;
				
			case 18: // Memcached::RES_PARTIAL_READ
				$result = "Partial network data read error.";
				break;
				
			case 19: // Memcached::RES_SOME_ERRORS
				$result = "Some errors occurred during multi-get.";
				break;
				
			case 20: // Memcached::RES_NO_SERVERS
				$result = "Server list is empty.";
				break;
				
			case 21: // Memcached::RES_END
				$result = "End of result set.";
				break;
				
			case 25: // Memcached::RES_ERRNO
				$result = "System error.";
				break;
				
			case 31: // Memcached::RES_BUFFERED
				$result = "The operation was buffered.";
				break;
				
			case 30: // Memcached::RES_TIMEOUT
				$result = "The operation timed out.";
				break;
				
			case 32: // Memcached::RES_BAD_KEY_PROVIDED
				$result = "Bad key.";
				break;
				
			case 11: // Memcached::RES_CONNECTION_SOCKET_CREATE_FAILURE
				$result = "Failed to create network socket.";
				break;
				
			case -1001: // Memcached::RES_PAYLOAD_FAILURE
				$result = "Payload failure: could not compress/decompress or serialize/unserialize the value.";
				break;
				
			default:
				$result = "An unknown error has occured.";
				break;
		}
		
		if($code!=0 && $code!=21) $this->_triggerError($result);
	}
	
	/**
     * Default get callback handler
	 *
	 * This is what is called if no other callback handler was used with get(). This only gets called when 
	 * get failed to find the key you were looking for.
     *
     * @access static
     */
	public function getFailCallback($memc, $item) {
		$this->_triggerError('Function getFailCallback() triggered with attempt to $this->mcd->get("'.$item.'").');
	}
	
	/**
     * Trigger an error if something did not complete as exptected
	 *
	 * This method gets triggered when something is incorrect with how a method was executed.
     *
	 * @param string $error	Error string
     * @param int $type		Type of error
     * @access private
     */
	private function _triggerError($error, $type=E_USER_NOTICE){
		$backtrace = debug_backtrace();
		$backtrace = array_reverse($backtrace);
		$error .= "\n";
		$i=1;
		foreach($backtrace as $errorcode){
			$file = ($errorcode['file']!='') ? "-> File: ".basename($errorcode['file'])." (line ".$errorcode['line'].")":"";
			$error .= "\n\t".$i.") ".$errorcode['class']."::".$errorcode['function']." {$file}";
			$i++;
		}
		trigger_error($error."\n\n", $type);
	}	
}
?>