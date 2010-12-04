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
 * @category Memcache Manager
 * @package MemcacheManager
 * @author Peter Schmalfeldt <manifestinteractive@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link http://code.google.com/p/memcachemanager/
 * @link http://groups.google.com/group/memcachemanager
 */
 
include('classes/class_MemcacheManager.php');

// creat memcache object
$mc = new MemcacheManager();

// add servers
$mc->addserver('localhost', 11211);
$mc->addserver('localhost', 11212);
$mc->addserver('localhost', 11213);

// create some date to store
$init_data = array(
	'username'=>'memcachehater',
	'email'=>'hater@hateseverything.com',
	'displayname'=>'Memcache Hater',
	'location'=>array(
		'country'=>'USA',
		'state'=>'Missouri',
		'city'=>'St. Louis'
	)
);
$replace_data = array(
	'username'=>'memcachelover',
	'email'=>'me@myemail.com',
	'displayname'=>'Memcache Lover',
	'location'=>array(
		'country'=>'USA',
		'state'=>'Oregon',
		'city'=>'Portland'
	)
);

// start making output readable in browser
echo '<pre>';

// store data
$mc->add('memcachelover', $init_data, 0, true, true, true);			// adds the key with JSON encoding, encryption and compression
$mc->replace('memcachelover', $replace_data, 0, true, true, true);	// replaces the key with JSON encoding, encryption and compression

// retrieve data
echo $mc->get('memcachelover', false, false, true)."\n\n";			// echo the uncompressed, but still encrypted key
echo $mc->get('memcachelover', false, true, true)."\n\n";			// echo the uncompressed, decrypted JSON formatted string
print_r($mc->get('memcachelover',true, true, true))."\n\n";			// print the uncompressed, decrypted array

// test increment and decrement
$question = "the answer to life the universe and everything = ";
if($mc->get('ultimate') != '') $mc->replace('ultimate', 30);		// check if key already exists...
else $mc->add('ultimate', 30);										// ...otherwise add it
$mc->increment('ultimate', 20);										// increment key
$mc->decrement('ultimate', 8);										// decrement key
echo $question.$mc->get('ultimate');								// echo key

// finish making output readable in browser
echo '</pre>';

// now, let's generate spiffy report :)
echo $mc->report();													// print our custom report
?>