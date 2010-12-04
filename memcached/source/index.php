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
 
include('classes/class_MemcachedManager.php');
include('classes/class_MemcachedReport.php');

// start making output readable in browser
echo '<pre>';

// creat memcached object with persistant id of 'pool' and key namespace prefix of 'mc_'
$mcd = new MemcachedManager('pool', 'mc_');

// add servers if the do not already exist
if(!count($mcd->getServerList())){
	$servers = array(
		array('localhost', 11211, 33),
		array('localhost', 11212, 33),
		array('localhost', 11213, 33)
	);
	$mcd->addServers($servers);
}

// set some values
$mcd->set('foo','value1');
$mcd->set('bar','value2');

// add some key tags
$mcd->addKeyTags('bar', array('tag_user_jane', 'tag_user_dick', 'tag_user_spot'));
$mcd->addKeyTags('foo', array('tag_user_jane', 'tag_user_john', 'tag_user_fido'));

// delete some key tags
$mcd->deleteMultiKeyTag(array('foo', 'bar'), 'tag_user_jane');

// search for keys with part of tag
$foundkeys = $mcd->searchForKeys('_user_');

// print array of found keys
print_r($foundkeys);

// delete all memcached keys and their tags using $keys
$mcd->cleanDelete($foundkeys);

// finish making output readable in browser
echo '</pre>';

// fun snazzy report
$report = new MemcachedReport($mcd->getStats());
?>