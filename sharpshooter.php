<?php
$api = "http://yoursite.com/cache/api";

function do_post_request($url, $postdata, $files = NULL) 
	{ 
		$data = ""; 
		$boundary = "---------------------" . substr(md5(rand(0, 32000)), 0, 10); 
		
		if(is_array($postdata))
			{ 
				foreach($postdata as $key => $val) 
					{ 
						$data .= "--" . $boundary . "\n"; 
						$data .= "Content-Disposition: form-data; name=" . $key . "\n\n" . $val . "\n"; 
					} 
			}
		     
		$data .= "--" . $boundary . "\n";

		if(is_array($files))
			{
		    
				foreach($files as $key => $file) 
					{ 
						$fileContents = file_get_contents($file['tmp_name']); 
		
						$data .= "Content-Disposition: form-data; name=" . $key . "; filename=" . $file['name'] . "\n"; 
						$data .= "Content-Type: application/x-bittorrent\n"; 
						$data .= "Content-Transfer-Encoding: binary\n\n"; 
						$data .= $fileContents . "\n"; 
						$data .= "--" . $boundary . "--\n"; 
					}
			}
		  
		$params = array('http' => array( 
				'method' => 'POST', 
				'header' => 'Content-Type: multipart/form-data; boundary=' . $boundary, 
				'content' => $data 
		)); 

		$ctx = stream_context_create($params); 
		$fp = @fopen($url, 'rb', false, $ctx); 
		   
		if (!$fp)
			throw new Exception("Problem with " . $url . ", " . $php_errormsg); 
		  
		$response = @stream_get_contents($fp); 

		if ($response === false)
			throw new Exception("Problem reading data from " . $url . ", " . $php_errormsg); 

		return $response; 
	} 

if(count($_POST))
	$post = $_POST;

$post['submit'] = "Upload";
$files['torrent_file'] = $_FILES['torrent_file'];

print_r(do_post_request($api, $post, $files));
?>
