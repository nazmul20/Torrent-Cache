Torrent-Cache
=============
### v 1.0.0

Written by Xan Manning (http://xan-manning.co.uk)

	"THE BEER-&-COFFEE-WARE LICENSE" [ Revision 0 ]:
	- Variation of the "BEER-WARE LICENSE" Rev 42.
	<xan . manning at gmail.com> wrote this file. As long as you retain this notice 
	you can do whatever you want with this stuff. If we meet some day, and you think
	this stuff is worth it, you can buy me a beer or coffee in return. Xan Manning.

Very simple, very small and quick, autonomous Torrent Cache. Includes a simple API, written as an alternative to Torrage.


[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=xan.manning&url=https://github.com/xanmanning/Torrent-Cache&title=Torrent-Cache&language=&tags=github&category=software) 

**BITCOIN DONATIONS**
 * 1BFhtdVYmtAuYostpZLDAiGt1dmJaHTRDW

DISCLAIMER
----------
	
This script was written for the purposes of distributing legal .torrent files. Having said that I will not be held responsible for the content that does get uploaded via this script, it was never my intention to ever aid in piracy, merely provide a service to store .torrent files.

BitTorrent protocol is not illegal in itself, it is used by individuals to share and collaborate large files amongst one another and distribute their own content to the world. A number of examples of this happening are in the Linux community; Ubuntu, Debian, Fedora - all of which and many more availablefor download via BitTorrent.

Please don't label me a pirate!



AUTHORS
-------

 *  Xan Manning (xan[dot]manning[at]gmail[dot]com)


INSTALLATION
------------

1. Copy index.php.dist to index.php
2. Change the $CONFIG variables in index.php
3. Upload to your server
4. CHMOD the containing folder to 0777.
5. Visit http://yoursite/path/to/cache/index.php
6. Watch it install.
7. CHMOD the containing folder to 0755.
8. Done.


TIPS AND TRICKS
---------------

1. Better protect users privacy by setting your server access and error logs to /dev/null
2. Enabling GZip helps keep storage requirements lower
3. Using mod_rewrite (or equivalent) makes this script more useful
4. Only allow API usage if you understand the risk that files being uploaded to your server may be illegal.

