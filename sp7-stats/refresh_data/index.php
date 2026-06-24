<?php

//require_once('../components/header.php');
require_once dirname(__DIR__) . '/components/header.php';

date_default_timezone_set('UTC');
//date_default_timezone_set('America/Chicago');

$no_gui_separator = "<br>\n";

$error = FALSE;

function alert($status,$message){

	global $no_gui;

	if($no_gui){
		global $no_gui_separator;
		echo ucfirst($status).' : '.$message.$no_gui_separator;
	}
	else
		echo '<div class="alert alert-'.$status.'">'.$message.'</div>';

	if($status=='danger'){
		global $error;
		$error = TRUE;
		exit();
	}

}

function prepare_dir($dir,$delete_files=TRUE){

	if(!file_exists($dir)){

		mkdir($dir);

		if(!file_exists($dir))
			alert('danger','Unable to create directory <i>'.$dir.'</i>. Please check your config and permissions');
		elseif(VERBOSE)
			alert('secondary','Directory <i>'.$dir.'</i> was created successfully');

	} // Create target directory
	elseif($delete_files) { // Delete everything from that directory if not empty

		$files = glob($dir.'*.*');
		$files_count = count($files);

		foreach($files as $file)
			if(is_file($file))
				unlink($file);

		$files = glob($dir.'*.*');

		if(count($files) == 0){
			if($files_count==0)
				alert('info','<i>'.$dir.'</i> is already empty. No files deleted');
			else
				alert('info','Deleted <b>'.$files_count.'</b> files from <i>'.$dir.'</i>');
		}
		else
			foreach($files as $file)
				alert('danger','Failed to delete <b>'.$dir.$file.'</b>');

	}

}

$total_lines = 0;
$first_time = FALSE;
$last_time = FALSE;

//memory management
//ini_set('memory_limit','32M');
unset($_GET,$_POST,$_FILES,$_SERVER,$_COOKIE);

//prepare to extract data
//require_once('../components/raw_data.php');
require_once dirname(__DIR__) . '/components/raw_data.php';

//prepare to compile institutions
//require_once('../components/institutions.php');
require_once dirname(__DIR__) . '/components/institutions.php';

//prepare to fetch information about user agent strings
//require_once('../components/user_agent_strings.php');
require_once dirname(__DIR__) . '/components/user_agent_strings.php';

//unzip all files
//run extract_data on each file
//run compile_institutions on each resulting file
//run get_data_for_user_agent_string and save new strings
//require_once('../components/unzip.php');
require_once dirname(__DIR__) . '/components/unzip.php';



finish_data_extraction();

//validate result of compilation and save institutions
compile_institutions_end();


if($error)
	alert('warning','There were some errors. Please review the messages above');
else {

	$misc_file_data = [
		'timestamp'=>time()
	];

	$misc_file_data['total_lines'] = $total_lines;
	$misc_file_data['first_time'] = $first_time;
	$misc_file_data['last_time'] = $last_time;

	file_put_contents(WORKING_LOCATION.'misc.json',json_encode($misc_file_data));
	alert('success','Success!');

}

alert('info','Current RAM usage: '.round(memory_get_usage()/1024/1024,2).
	'MB<br>Max RAM usage: '.round(memory_get_peak_usage()/1024/1024,2).
	'MB<br>RAM usage limit: '.ini_get('memory_limit'));
