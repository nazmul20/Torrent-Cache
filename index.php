<?php

// Torrent storage folder
$CONFIG['torrent_folder'] = "t";
# $CONFIG['torrent_folder'] = substr(md5($_SERVER['SERVER_ADDR'])), 0, 5); // Good to keep secure!

// Torrent storage name (title), FALSE value uses the default "Torrent Storage on " . $_SERVER['SERVER_NAME']
$CONFIG['torrent_name'] = FALSE;

// Torrent storage Tagline (Message under the name/title), FALSE leaves this blank
$CONFIG['torrent_tagline'] = FALSE;

// Apache/IIS Rewrite enabled? Needs to be like 
// http://yourdomain.com/id forwards to http://yourdomain.com/index.php?i=id or
// http://yourdomain.com/dir/id forwards to http://yourdomain.com/dir/index.php?i=id
// TRUE or FALSE
$CONFIG['torrent_rewrite'] = FALSE;
# $CONFIG['torrent_rewrite'] = TRUE;

// Enable GZip compression
// WARNING: Requires more CPU
$CONFIG['torrent_gzip'] = FALSE;
# $CONFIG['torrent_gzip'] = TRUE;


// Expiration time of torrent files (in days)
// Finds out when they were last accessed
// and if they have been sitting idle for this long they will
// be deleted (int or FALSE).
$CONFIG['torrent_expire'] = FALSE;
# $CONFIG['torrent_expire'] = 180;

// Starting ID length, recommended more than 3
// Default is 1;
$CONFIG['torrent_id_length'] = 3;
# $CONFIG['torrent_id_length'] = 10;

// Enable API?
// Allows the use of an API so external programs can store torrents.
// You need to point external programs to http://yourdomain.com/index.php?api or
// http://yourdomain.com/api (if you have rewrite enabled).
$CONFIG['torrent_api'] = FALSE;
# $CONFIG['torrent_api'] = TRUE;

// Use alternate stylesheet, FALSE uses default
// Note that URLs are accepted!
$CONFIG['torrent_style'] = FALSE;
# $CONFIG['torrent_style'] = "/path/to/sheet.css";


@error_reporting(E_ALL ^ E_NOTICE);
set_time_limit(0);

/* END Config, Nothing more to edit! */





















/* START torrent storage */

if($CONFIG['torrent_gzip'])
	ob_start("ob_gzhandler");

if(get_magic_quotes_gpc())
	{
		function callback_stripslashes(&$val, $name) 
			{
				if(get_magic_quotes_gpc()) 
		 			$val = stripslashes($val);
			}

		if(count($_GET))
			array_walk($_GET, 'callback_stripslashes');
		if(count($_POST))
	 		array_walk($_POST, 'callback_stripslashes');
	 	if(count($_COOKIE))
	 		array_walk($_COOKIE, 'callback_stripslashes');
	}

class torrent
	{
		public function __construct($config)
			{
				$this->config = $config;
			}

		public function read($file)
			{
				if(!file_exists($file))
					return false;

				$open = fopen($file, "r");
				$data = fread($open, filesize($file) + 1024);
				touch($file);
				fclose($open);
				return $data;
			}
			
		public function write($data, $file)
			{
				$open = fopen($file, "w");
				$write = fwrite($open, $data);
				fclose($open);
				
				return $write;
			}

		public function serializer($data)
			{
				$serialize = serialize($data);
				$output = $serialize;
				return $output;
			}
			
		public function deserializer($data)
			{
				$unserialize = unserialize($data);
				$output = $unserialize;
				return $output;
			}


		public function setDataPath($filename = FALSE, $justPath = FALSE)
			{
				if(!$filename && !$forceImage)
					return $this->config['torrent_folder'];
				
				$info = pathinfo($filename);

				$nonBasename = str_replace(".torrent", "", $filename);

				if(strtolower($info['extension']) == "torrent" || $justPath)
					{
						$path = $this->config['torrent_folder'] . "/" . substr($nonBasename, 0, 1);

						if(!file_exists($path) && is_writable($this->config['torrent_folder']))
							{
								mkdir($path);
								chmod($path, 0777);
								$this->write("FORBIDDEN", $path . "/index.html");
								chmod($path . "/index.html", 0666);
							}

						for ($i = 1; $i <= 2; $i++) {

							$parent = $path;
						   
							if(strlen($nonBasename) > $i)
								$path .= "/" . substr($nonBasename, $i, 1);

							if(!file_exists($path) && is_writable($parent))
								{
									mkdir($path);
									chmod($path, 0777);
									$this->write("FORBIDDEN", $path . "/index.html");
									chmod($path . "/index.html", 0666);
								}

						}


					} else
						return false;

				if($justPath)
					return $path;
				else
					return $path . "/" . $filename;
			}

		public function event($time)
			{
				$context = array(
        					array(60 * 60 * 24 * 365 , "years"),
        					array(60 * 60 * 24 * 7, "weeks"),
        					array(60 * 60 * 24 , "days"),
   		    				array(60 * 60 , "hours"),
        					array(60 , "minutes"),
						array(1 , "seconds"),
   			 		);
    
    					$now = gmdate('U');
   						$difference = $now - $time;
	
    
    					for ($i = 0, $n = count($context); $i < $n; $i++) {
        
        						$seconds = $context[$i][0];
        						$name = $context[$i][1];
        
        						if (($count = floor($difference / $seconds)) > 0) {
            				   			break;
        							}
    						}
    
    				$print = ($count == 1) ? '1 ' . substr($name, 0, -1) : $count . " " . $name;
				
				return $print;
    
			}

		public function setTitle($config)
			{
				if(!$config)
					$title = "Torrent Storage on " . $_SERVER['SERVER_NAME'];
				else
					$title = htmlspecialchars($config, ENT_COMPAT, 'UTF-8', FALSE);

				return $title;
			}

		public function setTagline($config)
			{
				if($this->config['torrent_gzip'])
					$gzip = "<li>Files are stored in <strong>gzip format</strong> making it time consuming to search for a file.</li>";

				if($this->config['torrent_expire'])
					$limit = "<li>Files are deleted after <strong>" . $this->event(time() - $this->config['torrent_expire'] * 24 * 60 * 60) . " of inactivity</strong>.</li>";

				if($this->config['torrent_api'])
					$api = "<h2>API Access</h2><div>API has been enabled and can be accessed through <a href=\"" . $this->linker('api') . "\">" . $this->linker('api') . "</a>.</div>
						<div>To initiate upload, send a <strong>POST request</strong> with a form element called <em>'submit'</em> and a file upload field called <em>'torrent_file'</em>.</div>
						<div>The API response is in JSON format.</div>";

				if(!$config)
					$output = "<div id=\"tagline\">
							<h2>Free torrent storage service!</h2>
							<div>This service is for storing .torrent files online, once the file is uploaded it cannot be searched for. On successful upload you are given a unique URL, such as <a href=\"" . $this->linker('h4x') . "\">" . $this->linker('h4x') . "</a>, that will allow you to download your file.</div>
							<h2>Please note</h2>
							<ul>
								<li>We <strong>do not</strong> log the uploader IP address.</li>
								<li>We <strong>do not</strong> index the files uploaded into a database.</li>
								<li>We <strong>can not</strong> tell what a file links to.</li>
								<li>The original .torrent filename <strong>is not</strong> saved.</li>
								<li>We <strong>are not</strong> a tracker.</li>
								<li>You <strong>can not</strong> search files that are stored here.</li>
								" . $gzip . "
								" . $limit . "
							</ul>
						</div>
						" . $api . "
						<div class=\"spacer\">&nbsp;</div>";
				else
					$output = "<div id=\"tagline\">" . $config . "</div><div class=\"spacer\">&nbsp;</div>";

				return $output;
			}

		public function titleID($requri = FALSE)
			{
				if(!$requri)
					$id = "Welcome!";
				else
					$id = $requri;

				return $id;
			}

		public function thisDir()
			{
				$output = dirname($_SERVER['SCRIPT_FILENAME']);
				return $output;
			}

		public function robotPrivacy($requri = FALSE)
			{
				if(!$requri)
					return "index,follow";
				
				else
					return "noindex,nofollow";
			}

		public function styleSheet()
			{
				if($this->config['torrent_style'] == FALSE)
					return false;

				if(preg_match("/^(http|https|ftp):\/\/(.*?)/", $this->config['torrent_style']))
					{
						$headers = @get_headers($this->config['torrent_style']);
						if (preg_match("|200|", $headers[0]))
							return true;
						else
							return false;
					} else
						{
							if(file_exists($this->config['torrent_style']))
								return true;
							else
								return false;
						}
				

			}

		public function compressFile($file, $rename = FALSE)
			{
				if(!$this->config['torrent_gzip'])
					return false;

				if(!file_exists($file))
					return false;

				$data = $this->read($file);
				$compressed = gzencode($data, 9);
				
				if($rename)
					$target = $rename;
				else
					$target = $file;

				if($rename)
					unlink($file);

				if($this->write($compressed, $target))
					{
						chmod($target, 0666);
						return true;
					}
				else
					return false;
			}

		public function decompressFile($file)
			{
				/* http://php.net/manual/en/function.gzdecode.php */
				$g = tempnam('/tmp','gzip-php');
				@file_put_contents($g, $file);
				ob_start();
				readgzfile($g);
				$d = ob_get_clean();
				return $d;
			}

		public function decompressedSize($content)
			{
				$g = tempnam('/tmp','gzip-php-decompressed');
				@file_put_contents($g, $content);
				return filesize($g);
			}

		public function checkFileNoExpire($file)
			{
				if(!$this->config['torrent_expire'])
					return true;

				$filetime = filemtime($file);
				$expire = $this->config['torrent_expire'] * 24 * 60 * 60;

				if(time() > ($filetime + $expire)) {
					unlink($file);
					return false;
				}
				else
					return true;
			}

		public function uploadFile($file, $rename = FALSE)
			{
				$info = pathinfo($file['name']);


				if($rename)
					$path = $this->setDataPath($rename . "." . strtolower($info['extension'])); 
				else
					$path = $path = $this->setDataPath($file['name']);

				if(strtolower($info['extension']) != "torrent")
					return false;

				if(!move_uploaded_file($file['tmp_name'], $path))
					return false;
				
				chmod($path, 0777);

				if($this->config['torrent_gzip'])
					$this->compressFile($path, $path . ".gz");

				return true;
			}


		public function checkID($id)
			{
				if($this->config['torrent_gzip'])
					$gz = ".gz";

				if(file_exists($this->setDataPath($id . ".torrent") . $gz))
					$output = TRUE;
				else
					$output = FALSE;
						
				return $output;
			}

		public function getLastID()
			{

				$lastIdFile = $this->setDataPath($this->token(TRUE, TRUE), TRUE)  . "/" . $this->token(TRUE, TRUE);

				$index = $this->deserializer($this->read($lastIdFile));

				if($index[0] < 1)
					$output = 1;
				else
					$output = $index[0];

				return $output;
			}

		public function linker($id = FALSE)
			{
				$dir = dirname($_SERVER['SCRIPT_NAME']);
				if(strlen($dir) > 1)
					$now = "http://" . $_SERVER['SERVER_NAME'] . $dir;
				else
					$now = "http://" . $_SERVER['SERVER_NAME'];

				$file = basename($_SERVER['SCRIPT_NAME']);
				
				switch($this->config['torrent_rewrite'])
					{
						case TRUE:
							if($id == FALSE)
								$output = $now . "/";
							else
								$output = $now . "/" . $id;
						break;
						case FALSE:
							if($id == FALSE)
								$output = $now . "/";
							else
								$output = $now . "/" . $file . "?" . $id;
						break;
					}

				return $output;
			}


		public function token($generate = FALSE, $server = FALSE)
			{
				if($generate == TRUE)
					{
						if($server == TRUE)
							$output = strtolower(sha1(md5($_SERVER['SERVER_ADDR'])));
						else
							$output = strtoupper(sha1(md5((int)date("G") . $_SERVER['REMOTE_ADDR'] . $this->token(TRUE, TRUE) . $_SERVER['SERVER_ADDR']. $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SCRIPT_FILENAME'])));

						return $output;
					}

				$time = array(
				((int)date("G") - 1), 
				((int)date("G")), 
				((int)date("G") + 1));

				if((int)date("G") == 23)
					$time[2] = 0;

				if((int)date("G") == 0)
					$time[0] = 23;

				$output = array(	strtoupper(sha1(md5($time[0] . $_SERVER['REMOTE_ADDR'] . $this->token(TRUE, TRUE) . $_SERVER['SERVER_ADDR']. $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SCRIPT_FILENAME']))),
							strtoupper(sha1(md5($time[1] . $_SERVER['REMOTE_ADDR'] . $this->token(TRUE, TRUE) . $_SERVER['SERVER_ADDR']. $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SCRIPT_FILENAME']))),
							strtoupper(sha1(md5($time[2] . $_SERVER['REMOTE_ADDR'] . $this->token(TRUE, TRUE) . $_SERVER['SERVER_ADDR']. $_SERVER['HTTP_USER_AGENT'] . $_SERVER['SCRIPT_FILENAME']))));

				return $output;
			}


		public function generateID($id = FALSE, $iterations = 0)
			{
				$checkArray = array('install', 'api', 'h4x');

				if($iterations > 0 && $iterations < 4 && $id != FALSE)
					$id = $this->generateRandomString($this->getLastID());
				elseif($iterations > 3 && $id != FALSE)
					{
						$id = $this->generateRandomString($this->getLastID() + 1);
						$lastIdFile = $this->setDataPath($this->token(TRUE, TRUE), TRUE)  . "/" . $this->token(TRUE, TRUE);
						$this->write($this->serializer(array($this->getLastID() + 1)), $lastIdFile);
					}
				else
					$id = $id;

				if(!$id)
					$id = $this->generateRandomString($this->getLastID());

				if($id == $this->config['torrent_folder'] || in_array($id, $checkArray))
					$id = $this->generateRandomString($this->getLastID());

				if($this->config['torrent_rewrite'] && (is_dir($id) || file_exists($id)))
					$id = $this->generateID($id, $iterations + 1);	

				if(!$this->checkID($id) && !in_array($id, $checkArray))
					return $id;
				else
					return $this->generateID($id, $iterations + 1);			
			}


		public function generateRandomString($length)
			{
				$checkArray = array('install', 'api', 'h4x');

				$characters = "123456789abcdefghijklmnopqrstuvwxyz";  
				$output = "";
					for ($p = 0; $p < $length; $p++) {
						$output .= $characters[mt_rand(0, strlen($characters))];
					}
					
				if(is_bool($output) || $output == NULL || strlen($output) < $length || in_array($output, $checkArray))
					return $this->generateRandomString($length);
				else
    					return (string)$output;
			}


	}

$requri = $_SERVER['REQUEST_URI'];
$scrnam = $_SERVER['SCRIPT_NAME'];
$reqhash = NULL;

$info = explode("/", str_replace($scrnam, "", $requri));

$requri = str_replace("?", "", $info[0]);

if(!file_exists('./INSTALL_LOCK') && $requri != "install")
	header("Location: " . $_SERVER['PHP_SELF'] . "?install");

if(file_exists('./INSTALL_LOCK') && $CONFIG['torrent_rewrite'])
	$requri = $_GET['i'];


if(strstr($requri, "@"))
	{
		$tempRequri = explode('@', $requri, 2);
		$requri = $tempRequri[0];
		$reqhash = $tempRequri[1];
	}

$torrent = new torrent($CONFIG);

if($requri == "install" || $requri == "")
	{
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo $torrent->setTitle($CONFIG['torrent_name']); ?> &raquo; <?php echo $torrent->titleID($requri); ?></title>
		<link rel="icon" type="image/vnd.microsoft.icon" href="favicon.ico" />
		<link rel="icon" type="image/png" href="favicon.png" />
		<meta name="generator" content="Torrent Host">
		<meta name="Description" content="A quick to set up Torrent Host" />	
		<meta name="Keywords" content="short url torrent file host gzip" />
		<meta name="Robots" content="<?php echo $torrent->robotPrivacy($requri); ?>" /> 
		<meta name="Author" content="RedBeard" />
		<script type="text/javascript">
			function submitTorrent(targetButton) {
				var disabledButton = document.createElement('input');
				var parentContainer = document.getElementById('submitContainer');
				disabledButton.setAttribute('value', 'Uploading...');
				disabledButton.setAttribute('type', 'button');
				disabledButton.setAttribute('disabled', 'disabled');
				disabledButton.setAttribute('id', 'dummyPost');
				targetButton.style.display = "none";
				parentContainer.appendChild(disabledButton);
				return true;
			}
		</script>
		<?php
		if($torrent->styleSheet())
				echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $CONFIG['torrent_style'] . "\" media=\"screen\" />";
			else {
		?>
		<style type="text/css">
			body { background: #fff; font-family: Arial, Helvetica, sans-serif; font-size: 12px; padding: 10%; }
			h2 { color: #3B3B3B; }
			a { color: #336699; }
			.success { background-color: #AAFFAA; border: 1px solid #00CC00; font-weight: bolder; text-align: center; padding: 2px; color: #000000; margin-top: 3px; margin-bottom: 3px; }
			.warn { background-color: #FFFFAA; border: 1px solid #CCCC00; font-weight: bolder; text-align: center; padding: 2px; color: #000000; margin-top: 3px; margin-bottom: 3px; }
			.error { background-color: #FFAAAA; border: 1px solid #CC0000; font-weight: bolder; text-align: center; padding: 2px; color: #000000; margin-top: 3px; margin-bottom: 3px; }
			.confirmURL { border-bottom: 1px solid #CCCCCC;  text-align: center; font-size: medium; }
			#torrentUpload { width: 75%; }
			#upload_form { border-top: 1px dashed #3B3B3B; border-bottom: 1px dashed #3B3B3B; padding: 5%; }
			form#torrent_upload { border: 1px solid #AAAAAA; background-color: #E3E3E3; padding: 3px; text-align: center; }
			#submit_torrent { width: 250px; padding: 5px; border: 1px solid #3B3B3B; background-color: #F8F9FE; }
			#dummyPost { width: 250px; padding: 5px; border: 1px solid #3B3B3B; background-color: #F8F9FE; }
		</style>
		<?php
			}
		?>


	</head>
	<body>
		<div id="site">
<?php
	}

switch($requri)
	{
		case "install":
			$stage = array();
?>
			<h1>Installing <?php echo $torrent->setTitle($torrentName); ?></h1>
			<div id="installer">
				<ul id="installer_steps">
					<li>Checking this directory is writable...
					<?php
					if(!is_writable($torrent->thisDir()))
						echo "<span class=\"error\">Directory is not writable!</span> - CHMOD to 0777";
					else
						{ 
							echo "<span class=\"success\">Directory is writable!</span>"; 
							$stage[] = 1; 
						}

					if(count($stage) > 0)
						{
					?>
					</li>
					<li>Setting up file structure... 
					<?php
						if(!is_dir($CONFIG['torrent_folder']))
							{
								mkdir($CONFIG['torrent_folder']);
								chmod($CONFIG['torrent_folder'], 0777);

								$torrent->write("FORBIDDEN", $CONFIG['torrent_folder'] . "/index.html"); 
								chmod($CONFIG['torrent_folder'] . "/index.html", 0666);

								if(!is_numeric($CONFIG['torrent_id_length']) || (int)$CONFIG['torrent_id_length'] < 0)
									$CONFIG['torrent_id_length'] = 1;

								$token = $torrent->token(TRUE, TRUE);
								$data = $torrent->serializer(array($CONFIG['torrent_id_length']));

								if($torrent->write($data, $torrent->setDataPath($token, TRUE) . "/" . $token))
									{
										chmod($torrent->setDataPath($token, TRUE) . "/" . $token, 0666);
										echo "<span class=\"success\">Structure created!</span>";
										$stage[] = 2;
									}
								else
									echo "<span class=\"error\">Failed</span> - Is directory '" . $CONFIG['torrent_folder'] . "' writable?";
							}
					?>
					</li>
					<?php
						}

					if(count($stage) > 1)
						{
					?>
					<li>Locking installation...
					<?php
						if(!$torrent->write(time(), './INSTALL_LOCK'))
							echo "<span class=\"error\">Writing Error</span>";
						else
							{ 
								echo "<span class=\"success\">Complete</span>"; 
								$stage[] = 1; 
								chmod('./INSTALL_LOCK', 0666); 
							}
					?>
					</li>
					<?php
						}
					?>
				</ul>
				<?php
				if(count($stage) > 2)
					{ 
						echo "<div id=\"confirmInstalled\"><a href=\"" . $torrent->linker() . "\">Continue</a> to your new installation!<br /></div>";
						echo "<div id=\"confirmInstalled\" class=\"warn\">It is recommended that you now CHMOD this directory to 0755.</div>"; 
					}
				?>
			</div>
<?php
		break;
		case NULL:
			?>
			<div id="torrentUpload">
				<h1><?php echo $torrent->setTitle($CONFIG['torrent_name']); ?></h1>
				<?php
					echo $torrent->setTagLine($CONFIG['torrent_tagline']);
				?>
				<div id="result">
					<?php
					$acceptTokens = $torrent->token();
					if(@$_POST['submit'] && strlen(@$_FILES['torrent_file']['name']) > 7 && in_array(@$_POST['api_token'], $acceptTokens))
						{
							echo "<a name=\"result\" style=\"display: none;\">#</a>";
							$torrent_id = $torrent->generateID();
							if($torrent->uploadFile($_FILES['torrent_file'], $torrent_id))
								echo "<div class=\"success\">Your torrent file has been uploaded!</div><div class=\"confirmURL\">URL to your torrent is <a href=\"" . $torrent->linker($torrent_id) . "\">" . $torrent->linker($torrent_id) . "</a></div>";
							else
								echo "<div class=\"error\">Upload of torrent file failed.</div>";

							echo "<div class=\"spacer\">&nbsp;</div>";
						}
					elseif(@$_POST['submit'] && strlen(@$_FILES['torrent_file']['name']) > 7 && !in_array(@$_POST['api_token'], $acceptTokens))
						echo "<div class=\"error\">You have an invalid session!</div>\n<div class=\"spacer\">&nbsp;</div>";
					elseif(@$_POST['submit'] && strlen(@$_FILES['torrent_file']['name']) < 8 && in_array(@$_POST['api_token'], $acceptTokens))
						echo "<div class=\"error\">Please select a valid .torrent File!</div>\n<div class=\"spacer\">&nbsp;</div>";
					else
						echo "&nbsp;";
					?>
				</div>
				<div id="upload_form">
					<form id="torrent_upload" action="<?php echo $torrent->linker(); ?>#result" method="post" name="torrent_upload" enctype="multipart/form-data">
						<input type="hidden" name="api_token" value="<?php echo $torrent->token(TRUE); ?>" />
						<div id="fileUploadContainer">
							<input type="hidden" name="MAX_FILE_SIZE" value="2097152" />
							<label>Upload a torrent file (.torrent, max size 2Mb)</label>
							<br />
							<input type="file" name="torrent_file" id="torrent_file" /></div> 
						<div class="spacer">&nbsp;</div>
						<div id="submitContainer" class="submitContainer"> 
							<input type="submit" name="submit" value="Upload file" onclick="return submitTorrent(this);" id="submit_torrent" /> 
						</div> 
					</form>
				</div>
			</div>
			<?php
		break;
		case "api":
			$acceptTokens = $torrent->token();

			if(!$CONFIG['torrent_api'] && !in_array($_POST['api_token'], $acceptTokens))
				die('	{
					"id":			0,
					"url":			"' . $torrent->linker() . '",
					"error":		"E0x",
					"message":		"API Disabled!"
					}');

			if(@$_POST['submit'] && strlen(@$_FILES['torrent_file']['name']) > 7)
				{
					$torrent_id = $torrent->generateID();
					if($torrent->uploadFile($_FILES['torrent_file'], $torrent_id))
						die('	{
							"id":			"' . $torrent_id . '",
							"url":			"' . $torrent->linker($torrent_id) . '",
							"error":		0,
							"message":		"YAY!"
							}');
					else
						die('	{
							"id":			0,
							"url":			"' . $torrent->linker() . '",
							"error":		"E02",
							"message":		".torrent upload failed!"
							}');
				}
			else
				die('	{
					"id":			0,
					"url":			"' . $torrent->linker() . '",
					"error":		"E01",
					"message":		"Please select a valid .torrent File!"
					}');
			
		break;
		default:
			$file_ID = $requri;
			$gz = NULL;

			if($CONFIG['torrent_gzip'])
				$gz = ".gz";

			if(!file_exists($torrent->setDataPath($file_ID . ".torrent") . $gz))
				die('.torrent file not found... ');

			$file = $torrent->setDataPath($file_ID . ".torrent") . $gz;

			if(!$torrent->checkFileNoExpire($file))
				die('File has expired...');

			touch($file);

			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private", false);
			if($CONFIG['torrent_gzip'])
				header('Content-Encoding: gzip');
			header("Content-Type: application/x-bittorrent");
			header("Content-Disposition: attachment; filename=" . strtoupper(sha1(md5($file_ID . ".torrent"))) . ".torrent");
			header("Content-Transfer-Encoding: binary");

			if($CONFIG['torrent_gzip'])
				{
					$output = $torrent->decompressFile(file_get_contents($file));
					header("Content-Length: " . $torrent->decompressedSize($output));
				}
			else
				{
					$output = file_get_contents($file);
					header("Content-Length: " . filesize($file));
				}

			echo $output;

			exit();
		break;
	}

if($requri == "install" || $requri == "")
	{
?>
		</div>
	</body>
</html>
<?php
	}

?>
