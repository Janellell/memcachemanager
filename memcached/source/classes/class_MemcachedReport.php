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

/**
 * Begin Document
 */

class MemcachedReport {
	/**
	 * HTML code of report
	 * @var string
	 * @see getReport()
	 */
	protected $html;

	public function __construct($status){
		$report = '';

		if(is_array($status)){
			// show possible issues ?
			$show_issues = true;
			$remaining_memory_warn = 1024;

			// colors for report
			$color_title = '4D89F9';
			$color_border = 'E4EDFD';
			$color_subtitle = '999';
			$color_active = '4D89F9';
			$color_inactive = 'C6D9FD';
			$color_header = 'C6D9FD';
			$color_section = 'E4EDFD';
			$color_row1 = 'FFF';
			$color_row2 = 'F7F7F7';
			$color_text1 = '555';
			$color_text2 = '7E94BE';
			$color_text_error = '990000';

			// table control
			$rowheight = 20;
			$firstcolwidth = 175;

			// add totals for summary
			$server_bytes = array();
			$server_limit_maxbytes = array();
			$total_accepting_conns = 0;
			$total_bytes = 0;
			$total_bytes_read = 0;
			$total_bytes_written = 0;
			$total_cas_badval = 0;
			$total_cas_hits = 0;
			$total_cas_misses = 0;
			$total_cmd_flush = 0;
			$total_cmd_get = 0;
			$total_cmd_set = 0;
			$total_conn_yields = 0;
			$total_connection_structures = 0;
			$total_curr_connections = 0;
			$total_curr_items = 0;
			$total_decr_hits = 0;
			$total_decr_misses = 0;
			$total_delete_hits = 0;
			$total_delete_misses = 0;
			$total_evictions = 0;
			$total_get_hits = 0;
			$total_get_misses = 0;
			$total_incr_hits = 0;
			$total_incr_misses = 0;
			$total_limit_maxbytes = 0;
			$total_listen_disabled_num = 0;
			$total_rusage_system = 0;
			$total_rusage_user = 0;
			$total_servers = 0;
			$total_threads = 0;
			$total_total_connections = 0;
			$total_total_items = 0;

			// get totals first for all servers
			foreach($status as $host=>$data){
				$total_servers += 1;
				foreach($data as $key=>$val){
					if($key=='accepting_conns') $total_accepting_conns += $val;
					if($key=='bytes') {
						$total_bytes += $val;
						$server_bytes[] = $val;
					}
					if($key=='bytes_read') $total_bytes_read += $val;
					if($key=='bytes_written') $total_bytes_written += $val;
					if($key=='cas_badval') $total_cas_badval += $val;
					if($key=='cas_hits') $total_cas_hits += $val;
					if($key=='cas_misses') $total_cas_misses += $val;
					if($key=='cmd_flush') $total_cmd_flush += $val;
					if($key=='cmd_get') $total_cmd_get += $val;
					if($key=='cmd_set') $total_cmd_set += $val;
					if($key=='conn_yields') $total_conn_yields += $val;
					if($key=='connection_structures') $total_connection_structures += $val;
					if($key=='curr_connections') $total_curr_connections += $val;
					if($key=='curr_items') $total_curr_items += $val;
					if($key=='decr_hits') $total_decr_hits += $val;
					if($key=='decr_misses') $total_decr_misses += $val;
					if($key=='delete_hits') $total_delete_hits += $val;
					if($key=='delete_misses') $total_delete_misses += $val;
					if($key=='evictions') $total_evictions += $val;
					if($key=='get_hits') $total_get_hits += $val;
					if($key=='get_misses') $total_get_misses += $val;
					if($key=='incr_hits') $total_incr_hits += $val;
					if($key=='incr_misses') $total_incr_misses += $val;
					if($key=='limit_maxbytes') {
						$total_limit_maxbytes += $val;
						$server_limit_maxbytes[] = $val;
					}
					if($key=='listen_disabled_num') $total_listen_disabled_num += $val;
					if($key=='rusage_system') $total_rusage_system += $val;
					if($key=='rusage_user') $total_rusage_user += $val;
					if($key=='threads') $total_threads += $val;
					if($key=='total_connections') $total_total_connections += $val;
					if($key=='total_items') $total_total_items += $val;
				}
			}

			// set image width
			$imagewidth = ($total_servers*25);
			if($imagewidth < 150 && $total_servers > 1) $imagewidth = 150;
			$totalwidth = ($imagewidth+320);

			// make text strings and labels ... code only supports up to 26 memcache connections with these labels, if you need more, add labels here (AA, AB ...)
			$alpha = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
			$servers = ($total_servers==1) ? 'Connection':'Connections';

			// configure totals for graphs
			$imageFreeMemory = round(($total_limit_maxbytes-$total_bytes)/(1024*1024),2);

			$imageUsedMemory = round($total_bytes/(1024*1024),2);
			$imageUsedPercent = round(($total_bytes/$total_limit_maxbytes)*100,2);
			$imageFreePercent = (100-$imageUsedPercent);
			$allocatedMemory = $this->bsize($total_limit_maxbytes);
			$usedMemory = $this->bsize($total_bytes);
			$usedMemoryPercent = round(($total_bytes/$total_limit_maxbytes)*100,2);
			$availableMemory = $this->bsize($total_limit_maxbytes-$total_bytes);
			$chl = '';
			$perc = '';
			$totals = '';
			for($i=0; $i<$total_servers; $i++){
				$totals .= round(($server_bytes[$i]/$server_limit_maxbytes[$i])*100).',';
				$chl .= $alpha[$i]."|";
				$perc .= '100,';
			}
			$chl = rtrim($chl,'|');
			$perc = rtrim($perc,',');
			$totals = rtrim($totals,',');

			// start report
			$report = "<div id='memcachereport' style='font-family: arial; width: ".($totalwidth+20)."px; margin: 0 auto;'>
			<h3 style='font-size: 16px; color: #{$color_title}; white-space: nowrap;'>Memache Report &nbsp;&rsaquo;&nbsp; {$total_servers} Server {$servers}</h3>
			<table style='font-size: 12px; width: 100%; border: 1px solid #{$color_border}; border-bottom: 0px;' cellpadding='0' cellspacing='0'>";

			$report .= "<tr><td align='center' style='padding: 5px;'><img src='http://chart.apis.google.com/chart?cht=p&amp;chd=t:{$imageFreePercent},{$imageUsedPercent}&amp;chs=320x150&amp;chl=Free%20".str_replace(' ', '%20', $availableMemory)."|Used%20".str_replace(' ', '%20', $usedMemory)."&amp;chco={$color_inactive},{$color_active}' style='float: left;' />";
			if($total_servers>1) $report .= "<img src='http://chart.apis.google.com/chart?cht=bvs&amp;chs=".($total_servers*25)."x150&amp;chd=t:{$totals}|{$perc}&amp;chco={$color_active},{$color_inactive}&amp;chbh=20&amp;chds=0,100&amp;chl={$chl}&amp;chm=N*f*%,{$color_active},0,-1,9' style='float: right;' />";
			$report .= "</td></tr></table>";

			// if there is more than one connection, show accumulative summary
			if($total_servers>1){

				// check for possible issues
				$total_evictions_display = ($show_issues && $total_evictions > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_evictions)." !</span>":$total_evictions;
				$total_memory_available = ($show_issues && ($total_limit_maxbytes-$total_bytes) < $remaining_memory_warn) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".$this->bsize($total_limit_maxbytes-$total_bytes)." !</span>":$this->bsize($total_limit_maxbytes-$total_bytes);
				$total_get_misses_display = ($show_issues && $total_get_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_get_misses)." !</span>":number_format($total_get_misses);
				$total_delete_misses_display = ($show_issues && $total_delete_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_delete_misses)." !</span>":number_format($total_delete_misses);
				$total_incr_misses_display = ($show_issues && $total_incr_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_incr_misses)." !</span>":number_format($total_incr_misses);
				$total_decr_misses_display = ($show_issues && $total_decr_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_decr_misses)." !</span>":number_format($total_decr_misses);
				$total_cas_misses_display = ($show_issues && $total_cas_misses > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($total_cas_misses)." !</span>":number_format($total_cas_misses);

				// add to report
				$report .= "<table style='font-size: 12px; width: 100%; border: 1px solid #{$color_border};' cellpadding='0' cellspacing='0'>
				<tr><td colspan='2' style='font-size: 14px; background-color: #{$color_header}; padding: 5px;'><b>Accumulative Memcache Report</b></td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Server Statistics</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='Total system time for this instance (seconds:microseconds).'><td height='{$rowheight}' align='right' style='color:#{$color_text1}' width='{$firstcolwidth}'>System CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_rusage_system} Seconds</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total user time for this instance (seconds:microseconds).'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>User CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_rusage_user} Seconds</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memory Usage</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of bytes this server is allowed to use for storage.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory Allocation &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_limit_maxbytes)."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Current number of bytes used by this server to store items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory In Use &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_bytes)."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Current number of bytes available to be used by this server to store items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory Available &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_memory_available}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of bytes read by this server from network.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Read Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_bytes_read)."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Total number of bytes sent by this server to network.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Written Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($total_bytes_written)."</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Connection Information</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='Current number of open connections.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Current Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_curr_connections)."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of connections opened since the server started running.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_total_connections)."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of yields for connections.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Connection Yields &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_conn_yields)."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of connection structures allocated by the server.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Connection Structures &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($total_connection_structures)."</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memcache Statistics</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='The number of times socket listeners were disabled due to hitting the connection limit.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Listeners Disabled &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_listen_disabled_num}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of valid items removed from cache to free memory for new items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Evections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_evictions_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Total number of flush requests.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CMD Flush Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cmd_flush}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of retrieval requests.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CMD Get Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cmd_get}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Total number of storage requests.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CMD Set Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cmd_set}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of keys that have been compared and swapped, but the comparison (original) value did not match the supplied value.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CAS Bad Value &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cas_badval}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been compared and swapped and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CAS Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cas_hits}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been compared and swapped and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>CAS Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_cas_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been requested and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Get Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_get_hits}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been requested and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Get Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_get_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been deleted and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Delete Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_delete_hits}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been delete and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Delete Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_delete_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been incremented and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Increment Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_incr_hits}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been incremented and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Increment Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_incr_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been decremented and found present.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Decrement Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_decr_hits}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been decremented and not found.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Decrement Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_decr_misses_display}</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Item Information</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='Current number of items stored by this instance.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Current Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_curr_items}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of items stored during the life of this instance.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Total Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$total_total_items}</td></tr>
				</table>";
			}

			// show sumamry for each with alpha marker from graph
			$i=0;
			foreach($status as $host=>$data){
				if(!isset($data['rusage_system'])) {
					$data['rusage_system'] = '';
				}
				if(!isset($data['rusage_user'])) {
					$data['rusage_user'] = '';
				}
				if(!isset($data['cmd_flush'])) {
					$data['cmd_flush'] = 0;
				}
				if(!isset($data['conn_yields'])) {
					$data['conn_yields'] = 0;
				}
				if(!isset($data['cas_misses'])) {
					$data['cas_misses'] = 0;
				}
				if(!isset($data['decr_misses'])) {
					$data['decr_misses'] = 0;
				}
				if(!isset($data['incr_misses'])) {
					$data['incr_misses'] = 0;
				}
				if(!isset($data['delete_misses'])) {
					$data['delete_misses'] = 0;
				}
				if(!isset($data['listen_disabled_num'])) {
					$data['listen_disabled_num'] = 0;
				}
				if(!isset($data['cas_badval'])) {
					$data['cas_badval'] = 0;
				}
				if(!isset($data['cas_hits'])) {
					$data['cas_hits'] = 0;
				}
				if(!isset($data['delete_hits'])) {
					$data['delete_hits'] = 0;
				}
				if(!isset($data['incr_hits'])) {
					$data['incr_hits'] = 0;
				}
				if(!isset($data['decr_hits'])) {
					$data['decr_hits'] = 0;
				}

				list($host, $port) = explode(":", $host);

				$letter = ($total_servers>1) ? "[ ".$alpha[$i]." ]&nbsp; ":"";
				$currentUsedMemory = $this->bsize($data['bytes']);
				$currentAvailableMemory = $this->bsize($data['limit_maxbytes']-$data['bytes']);
				$currentUsedPercent = round(($data['bytes']/$data['limit_maxbytes'])*100,2);
				$currentFreePercent = (100-$currentUsedPercent);
				$accept = (isset($data['accepting_conns']) && $data['accepting_conns']==1) ? 'Yes':'No';

				// check for possible issues
				$evictions_display = ($show_issues && $data['evictions'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['evictions'])." !</span>":$data['evictions'];
				$memory_available = ($show_issues && ($data['limit_maxbytes']-$data['bytes']) < $remaining_memory_warn) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".$this->bsize($data['limit_maxbytes']-$data['bytes'])." !</span>":$this->bsize($data['limit_maxbytes']-$data['bytes']);
				$get_misses_display = ($show_issues && $data['get_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['get_misses'])." !</span>":number_format($data['get_misses']);

				$delete_misses_display = ($show_issues && $data['delete_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['delete_misses'])." !</span>":number_format($data['delete_misses']);
				$incr_misses_display = ($show_issues && $data['incr_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['incr_misses'])." !</span>":number_format($data['incr_misses']);
				$decr_misses_display = ($show_issues && $data['decr_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['decr_misses'])." !</span>":number_format($data['decr_misses']);
				$cas_misses_display = ($show_issues && $data['cas_misses'] > 0) ? "<span style='color:#{$color_text_error}; text-decoration:blink;'>".number_format($data['cas_misses'])." !</span>":number_format($data['cas_misses']);

				// add to report
				$report .= "<table class='memcachereport' style='font-size: 12px; width: 100%; margin-top: 10px; border: 1px solid #{$color_border};' cellpadding='0' cellspacing='0'>";
				if($total_servers>1) $report .= "<tr><td colspan='2' style='padding: 5px;' align='center'><img src='http://chart.apis.google.com/chart?cht=p&amp;chd=t:{$currentFreePercent},{$currentUsedPercent}&amp;chs=300x75&amp;chl=Free%20".str_replace(' ', '%20', $currentAvailableMemory)."|Used%20".str_replace(' ', '%20', $currentUsedMemory)."&amp;chco={$color_inactive},{$color_active}' /></td></tr>";

				$report .= "<tr><td colspan='2' style='font-size: 14px; background-color: #{$color_header}; padding: 5px;'><b>{$letter}{$host} &nbsp; &nbsp;&rsaquo;&nbsp; {$port}</b></td></tr>
				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Server Statistics</b></td></tr>

				<tr style='background-color: #{$color_row1};' title='1 or 0 to indicate whether the server is currently accepting connections or not.'><td width='{$firstcolwidth}' align='right' height='{$rowheight}' style='color:#{$color_text1}'>Accepting Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$accept."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Version string of this instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Memcache Version &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['version']."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Process id of the memcached instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Process ID &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['pid']."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Size of pointers for this host specified in bits (32 or 64).'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Pointer Size &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['pointer_size']."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of worker threads requested.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Threads &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['threads']."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Total system time for this instance (seconds:microseconds).'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>System CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['rusage_system']."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total user time for this instance (seconds:microseconds).'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>User CPU Usage &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$data['rusage_user']."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Start Time for this memcached instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Start Time &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".date('F jS, Y g:i:sA T',$data['time']-$data['uptime'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Uptime for this memcached instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Uptime &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->duration($data['time']-$data['uptime'])."</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memory Usage</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of bytes this server is allowed to use for storage.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Memory Allocation &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['limit_maxbytes'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Current number of bytes used by this server to store items.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Memory In Use &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['bytes'])."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Current number of bytes available to be used by this server to store items.'><td height='{$rowheight}' align='right' style='color:#{$color_text1}'>Memory Available &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$memory_available}</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of bytes read by this server from network.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Read Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['bytes_read'])."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Total number of bytes sent by this server to network.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Written Memory &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".$this->bsize($data['bytes_written'])."</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Connection Information</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='Current number of open connections.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Current Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['curr_connections'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of connections opened since the server started running. 	'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Connections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['total_connections'])."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of yields for connections.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Connection Yields &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['conn_yields'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of connection structures allocated by the server.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Connection Structures &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['connection_structures'])."</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Memcache Statistics</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='The number of times socket listeners were disabled due to hitting the connection limit.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Listeners Disabled &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['listen_disabled_num'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of valid items removed from cache to free memory for new items.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Evections &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$evictions_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Total number of flush requests.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CMD Flush Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cmd_flush'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of retrieval requests.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CMD Get Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cmd_get'])."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Total number of storage requests.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CMD Set Used &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cmd_set'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of keys that have been compared and swapped, but the comparison (original) value did not match the supplied value.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CAS Bad Value &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cas_badval'])."</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been compared and swapped and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CAS Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['cas_hits'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been compared and swapped and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>CAS Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$cas_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been requested and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Get Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['get_hits'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been requested and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Get Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$get_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been deleted and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Delete Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['delete_hits'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been delete and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Delete Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$delete_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been incremented and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Increment Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['incr_hits'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been incremented and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Increment Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$incr_misses_display}</td></tr>
				<tr style='background-color: #{$color_row1};' title='Number of keys that have been decremented and found present.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Decrement Hits &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['decr_hits'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Number of items that have been decremented and not found.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Decrement Misses &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;{$decr_misses_display}</td></tr>

				<tr><td colspan='2' style='font-size: 12px; background-color: #{$color_section}; padding: 5px;'><b>Item Information</b></td></tr>
				<tr style='background-color: #{$color_row1};' title='Current number of items stored by this instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Current Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['curr_items'])."</td></tr>
				<tr style='background-color: #{$color_row2};' title='Total number of items stored during the life of this instance.'><td align='right' height='{$rowheight}' style='color:#{$color_text1}'>Total Items &nbsp;&rsaquo;&nbsp;</td><td style='color:#{$color_text2}'>&nbsp;".number_format($data['total_items'])."</td></tr>

				</table><br />
				<a href='http://www.manifestinteractive.com' style='font-size: 10px; color: #{$color_subtitle}; text-decoration: none;' target='_blank'>Developed by Peter Schmalfeldt of Manifest Interactive, LLC</a>
				";
				$i++;
			}
			$report .= "</div>";
			$this->html = $report;
		}
	}

	/**
	 * Get the HTML code of the report;
	 *
	 * @return string
	 */
	public function getReport() {
		return $this->html;
	}

	/**
	  * Convert bytes into human readable format
	  *
	  * @param int $s Size to convert
	  * @return string Size Measurement
	  */
	private function bsize($s){
		foreach (array('','K','M','G') as $i => $k) {
			if ($s < 1024) break;
			$s/=1024;
		}
		return round($s,2)." {$k}B";
	}

	/**
	  * Get Time Duration from Passed Unicode Time
	  *
	  * @param int $ts Unicode Time
	  * @return string Time Duration
	  */
	private function duration($ts) {
		$time = time();
		$years = (int)((($time - $ts)/(7*86400))/52.177457);
		$rem = (int)(($time-$ts)-($years * 52.177457 * 7 * 86400));
		$weeks = (int)(($rem)/(7*86400));
		$days = (int)(($rem)/86400) - $weeks*7;
		$hours = (int)(($rem)/3600) - $days*24 - $weeks*7*24;
		$mins = (int)(($rem)/60) - $hours*60 - $days*24*60 - $weeks*7*24*60;
		$str = '';
		if($years==1) $str .= "$years year, ";
		if($years>1) $str .= "$years years, ";
		if($weeks==1) $str .= "$weeks week, ";
		if($weeks>1) $str .= "$weeks weeks, ";
		if($days==1) $str .= "$days day,";
		if($days>1) $str .= "$days days,";
		if($hours == 1) $str .= " $hours hour and";
		if($hours>1) $str .= " $hours hours and";
		if($mins == 1) $str .= " 1 minute";
		else $str .= " $mins minutes";
		return $str;
	}
}
?>