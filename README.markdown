Introduction
============

PHP Classes & Examples for both Memcache & Memcached modules. Source code includes Sample Reports as well as Documentation. Currently tested to work with memcached 1.4.3 & pecl extension 2.2.5.

Our Memcached Class now supports key tagging !!! Add by key, delete by key, search by key, use namespacing... whatever... you're the boss ;)

Also, if you have not already checked it out, we have a sweet tool to generate a report on your Memcached memory usage. See our Sample Report !

http://www.manifestinteractive.com/usenet/memcached/reports/report_sample.html

This project will host PHP Classes & Examples for both Memcache & Memcached modules. Source code includes Sample Reports as well as Documentation. Currently tested to work with memcached 1.4.3 & pecl extension 2.2.5.

Details
=======

My efforts are to make it easier to "visualize" our Memcache servers. I would love to hear your thoughts on ways this might be achieved. So far I am focusing on:

* Showing Visual Graphs with Memory Usage
* Highlighting Potential Problems that may need to be addressed
* Still leaving room for your own customizations
* I look forward to any feedback you have and am happy to work in any worthwhile features you may want added.

Known Issue with PECL 2.2.5 & Memcached 1.4.3
=============================================

There is a known issue that might cause some headaches for some people. Fortunately Harv over at php.net has already worked out a solution. If you are running Memcached 1.4.3 with the PECL extension 2.2.5, you will not be able to delete a key. If you did a var_dump(debug_backtrace()); you would see an error that reads CLIENT_ERROR bad command line format. Usage: delete <key> [noreply].

To fix this, search for a file named memcache.c and go to line 1494 and in the mmc_delete() function replace:

	command_len = spprintf(&command, 0, "delete %s %d", key, time);

with

	command_len = spprintf(&command, 0, "delete %s", key);

Once you have saved the file... just recompile, restart apache, and you are good to go.

Here is the Original Thread where I found the fix from Harv.

Hope this helps somebody like it helped me.

Definitions
===========

In case you have had a rough time trying to wrap your head around what some of the variables are actually for... here is a comprehensive list to the resources where we discovered this information. Hope this helps!

While you are reading these statistical definitions... keep in mind that the most useful statistics from those given here are the number of cache hits, misses, and evictions.

A large number of get_misses may just be an indication that the cache is still being populated with information. The number should, over time, decrease in comparison to the number of cache get_hits. If, however, you have a large number of cache misses compared to cache hits after an extended period of execution, it may be an indication that the size of the cache is too small and you either need to increase the total memory size, or increase the number of the memcached instances to improve the hit ratio.

A large number of evictions from the cache, particularly in comparison to the number of items stored is a sign that your cache is too small to hold the amount of information that you regularly want to keep cached. Instead of items being retained in the cache, items are being evicted to make way for new items keeping the turnover of items in the cache high, reducing the efficiency of the cache.

Sample Statistics Output
------------------------

	Array
	(
		[localhost:11211] => Array
			(
				[pid] => 24187
				[uptime] => 135454
				[time] => 1258121472
				[version] => 1.4.3
				[pointer_size] => 32
				[rusage_user] => 0.537918
				[rusage_system] => 1.757732
				[curr_connections] => 8
				[total_connections] => 1874
				[connection_structures] => 57
				[cmd_get] => 16287
				[cmd_set] => 7223
				[cmd_flush] => 33
				[get_hits] => 9940
				[get_misses] => 6347
				[delete_misses] => 0
				[delete_hits] => 0
				[incr_misses] => 0
				[incr_hits] => 1
				[decr_misses] => 0
				[decr_hits] => 1
				[cas_misses] => 0
				[cas_hits] => 0
				[cas_badval] => 0
				[bytes_read] => 234200204
				[bytes_written] => 272407668
				[limit_maxbytes] => 134217728
				[accepting_conns] => 1
				[listen_disabled_num] => 0
				[threads] => 4
				[conn_yields] => 0
				[bytes] => 59464725
				[curr_items] => 3430
				[total_items] => 7145
				[evictions] => 0
			)

		[localhost:11212] => Array
			(
				[pid] => 24226
				[uptime] => 135451
				[time] => 1258121472
				[version] => 1.4.3
				[pointer_size] => 32
				[rusage_user] => 0.131979
				[rusage_system] => 0.821875
				[curr_connections] => 5
				[total_connections] => 651
				[connection_structures] => 7
				[cmd_get] => 121
				[cmd_set] => 56
				[cmd_flush] => 0
				[get_hits] => 116
				[get_misses] => 5
				[delete_misses] => 0
				[delete_hits] => 2
				[incr_misses] => 0
				[incr_hits] => 52
				[decr_misses] => 0
				[decr_hits] => 52
				[cas_misses] => 0
				[cas_hits] => 0
				[cas_badval] => 0
				[bytes_read] => 11672
				[bytes_written] => 445678
				[limit_maxbytes] => 134217728
				[accepting_conns] => 1
				[listen_disabled_num] => 0
				[threads] => 4
				[conn_yields] => 0
				[bytes] => 453
				[curr_items] => 2
				[total_items] => 49
				[evictions] => 0
			)

		[localhost:11213] => Array
			(
				[pid] => 24280
				[uptime] => 135447
				[time] => 1258121471
				[version] => 1.4.3
				[pointer_size] => 32
				[rusage_user] => 0.156976
				[rusage_system] => 0.249962
				[curr_connections] => 5
				[total_connections] => 626
				[connection_structures] => 8
				[cmd_get] => 0
				[cmd_set] => 14
				[cmd_flush] => 0
				[get_hits] => 0
				[get_misses] => 0
				[delete_misses] => 0
				[delete_hits] => 0
				[incr_misses] => 0
				[incr_hits] => 0
				[decr_misses] => 0
				[decr_hits] => 0
				[cas_misses] => 0
				[cas_hits] => 0
				[cas_badval] => 0
				[bytes_read] => 9738
				[bytes_written] => 444431
				[limit_maxbytes] => 134217728
				[accepting_conns] => 1
				[listen_disabled_num] => 0
				[threads] => 4
				[conn_yields] => 0
				[bytes] => 394
				[curr_items] => 1
				[total_items] => 1
				[evictions] => 0
			)

	)

Server Statistics
-----------------

	accepting_conns	Accepting Connections	1 or 0 to indicate whether the server is currently accepting connections or not.
	version	 		Memcache Version	 	Version string of this instance.
	pid	 			Process ID	 			Process id of the memcached instance.
	pointer_size	Pointer Size	 		Size of pointers for this host specified in bits (32 or 64).
	threads	 		Threads	 				Number of worker threads requested.
	rusage_system	System CPU Usage		Total system time for this instance (seconds:microseconds).
	rusage_user	 	User CPU Usage	 		Total user time for this instance (seconds:microseconds).
	time	 		Time	 				Current time (as epoch).
	uptime	 		Uptime	 				Uptime for this memcached instance (seconds:microseconds).

To get the start time for your service, just subtract time from uptime (uptime-time).

Memory Usage
------------

	limit_maxbytes	Memory Allocation		Number of bytes this server is allowed to use for storage.
	bytes			Memory In Use			Current number of bytes used by this server to store items.
	bytes_read		Total Read Memory		Total number of bytes read by this server from network.
	bytes_written	Total Written Memory	Total number of bytes sent by this server to network.

To get available memory, just subtract bytes from limit_maxbytes (limit_maxbytes-bytes).

Connection Information
----------------------

	curr_connections	 	Current Connections		Current number of open connections.
	total_connections	 	Total Connections		Total number of connections opened since the server started running.
	conn_yields	 			Connection Yields		Number of yields for connections.
	connection_structures	Connection Structures	Number of connection structures allocated by the server.

Still not 100% sure what connection_structures or conn_yields are... Anyone?

Memcache Statistics
-------------------

	listen_disabled_num	Listeners Disabled	The number of times socket listeners were disabled due to hitting the connection limit.
	evictions			Evections	 		Number of valid items removed from cache to free memory for new items.
	cmd_flush	 		CMD Flush Used	 	Total number of flush requests.
	cmd_get	 			CMD Get Used	 	Total number of retrieval requests.
	cmd_set	 			CMD Set Used	 	Total number of storage requests.
	cas_badval	 		CAS Bad Value	 	Number of keys that have been compared and swapped, but the comparison (original) value did not match the supplied value.
	cas_hits	 		CAS Hits	 		Number of keys that have been compared and swapped and found present.
	cas_misses	 		CAS Misses	 		Number of items that have been compared and swapped and not found.
	get_hits	 		Get Hits	 		Number of keys that have been requested and found present.
	get_misses	 		Get Misses	 		Number of items that have been requested and not found.
	delete_hits	 		Delete Hits	 		Number of keys that have been deleted and found present.
	delete_misses	 	Delete Misses	 	Number of items that have been delete and not found.
	incr_hits	 		Increment Hits	 	Number of keys that have been incremented and found present.
	incr_misses	 		Increment Misses	Number of items that have been incremented and not found.
	decr_hits	 		Decrement Hits	 	Number of keys that have been decremented and found present.
	decr_misses	 		Decrement Misses	Number of items that have been decremented and not found.

Pretty much anything that is a miss can be bad. Evictions can be bad too. Read the notes above for more info.

Item Information
----------------

	curr_items	 Current Items	 Current number of items stored by this instance.
	total_items	 Total Items	 Total number of items stored during the life of this instance.

And that does it... for this version of Memcached anyway (1.4.3)