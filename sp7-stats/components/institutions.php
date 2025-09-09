<?php

global $institutions3;
global $institutions_dir;
global $institutions2_dir;


//contains some data about the institutions in each file (used on the main page)
//institutions/(tsv_file_name).json >
// - institution_name_urlencoded
//   - discipline_name_urlencoded
//     - collection_name_urlencoded
//       - sp7_version
//         - sorted distinct array
//       - sp6_version
//         - sorted distinct array
//       - isa_number
//         - sorted distinct array
//       - ip_address
//         - distinct array
//       - browser
//         - distinct array
//       - os
//         - distinct array
//       - count


//contains IDs for each institution
//institutions_ids.json
// - institution_id
//   - institution_name_urlencoded


//contains all of the data about each institution (used on the institutions page)
//institutions2/(institution_id).json
// - discipline_name_urlencoded
//   - collection_name_urlencoded
//     - year
//       - month
//         - day
//           - count

//contains brief information about each institution
//institutions.json
// - institution_name_urlencoded
//   - discipline_name_urlencoded
//     - collection_name_urlencoded
//       - count


$institutions_dir = WORKING_LOCATION.'institutions/';
prepare_dir($institutions_dir);
$institutions2_dir = WORKING_LOCATION.'institutions2/';
prepare_dir($institutions2_dir);

$institutions3 = [];//contains brief data about all institutions

$file_count = 0;

function compile_institutions($lines_data, $file_name){

	global $institutions3;
	global $institutions_dir;


	$result_data = [];
	$cols = ['sp7_version','sp6_version','isa_number','ip_address','browser','os','domain'];

	foreach($lines_data as $line_data){

		$ip_address = $line_data['ip'];
		$sp7_version = $line_data['version'];
		$sp6_version = $line_data['dbVersion'];
		$institution = $line_data['institution'];
		$discipline = $line_data['discipline'];
		$collection = $line_data['collection'];
		// $isa_number = $line_data['isaNumber'];
		$isa_number = $line_data['isaNumber'] ?? '';
		$browser = $line_data['browser'];
		$domain = $line_data['domain'];
		$os = $line_data['os'];

		//institution
		if(!array_key_exists($institution, $result_data))
			$result_data[$institution] = [];

		//discipline
		if(!array_key_exists($discipline, $result_data[$institution]))
			$result_data[$institution][$discipline] = [];

		//collection
		if(!array_key_exists($collection, $result_data[$institution][$discipline]))
			$result_data[$institution][$discipline][$collection] = [];

		if($sp7_version!=='' && $sp7_version[0]==='v')
			$sp7_version = substr($sp7_version,1);

		foreach($cols as $col){

			if(!array_key_exists($col, $result_data[$institution][$discipline][$collection]))
				$result_data[$institution][$discipline][$collection][$col] = [];

			if($$col!='' && array_search($$col,$result_data[$institution][$discipline][$collection][$col])===FALSE)
				$result_data[$institution][$discipline][$collection][$col][] = $$col;

		}


		if(!array_key_exists('count',$result_data[$institution][$discipline][$collection]))
			$result_data[$institution][$discipline][$collection]['count'] = 1;
		else
			$result_data[$institution][$discipline][$collection]['count']++;


	}


	$file_name = $institutions_dir.$file_name.'.json';
	file_put_contents($file_name,json_encode($result_data));


	if(!file_exists($file_name))
		alert('danger','Failed to create <i>'.$file_name.'</i>');
	elseif(VERBOSE)
		alert('secondary','<i>'.$file_name.'</i> was successfully created');

	foreach($result_data as $institution => $discipline_data){//add data to  institutions3

		if(!array_key_exists($institution,$institutions3))
			$institutions3[$institution] = [];

		foreach($discipline_data as $discipline => $collection_data){

			if(!array_key_exists($discipline,$institutions3[$institution]))
				$institutions3[$institution][$discipline] = [];

			foreach($collection_data as $collection => $data)

				if(!array_key_exists($collection,$institutions3[$institution][$discipline]))
					$institutions3[$institution][$discipline][$collection] = $data['count'];
				else
					$institutions3[$institution][$discipline][$collection] += $data['count'];

		}

	}

}


function compile_institutions_end(){

	global $institutions3;
	global $institutions_dir;
	global $institutions2_dir;


	$institutions2 = [];

	$files = glob($institutions_dir.'*.json');
	$file_count = count($files);

	foreach($files as $file){

		$unix_day = explode('/',$file);
		$unix_day = end($unix_day);
		$unix_day = explode('.',$unix_day);
		$unix_day = $unix_day[0];

		$unix_time = $unix_day*86400;
		$year = date(YEAR_FORMATTER, $unix_time);
		$month = date(MONTH_FORMATTER, $unix_time);
		$day = date(DAY_FORMATTER, $unix_time);

		$file_data = json_decode(file_get_contents($file),true);

		foreach($file_data as $institution => &$discipline_data){//add data to institutions2

			if(!array_key_exists($institution,$institutions2))
				$institutions2[$institution] = [];

			foreach($discipline_data as $discipline => &$collection_data){

				if(!array_key_exists($discipline,$institutions2[$institution]))
					$institutions2[$institution][$discipline] = [];

				foreach($collection_data as $collection => &$data){

					if(!array_key_exists($collection,$institutions2[$institution][$discipline]))
						$institutions2[$institution][$discipline][$collection] = [];

					if(!array_key_exists($year,$institutions2[$institution][$discipline][$collection]))
						$institutions2[$institution][$discipline][$collection][$year] = [];

					if(!array_key_exists($month,$institutions2[$institution][$discipline][$collection][$year]))
						$institutions2[$institution][$discipline][$collection][$year][$month] = [];

					if(!array_key_exists($day,$institutions2[$institution][$discipline][$collection][$year][$month]))
						$institutions2[$institution][$discipline][$collection][$year][$month][$day] = $data['count'];

					else
						$institutions2[$institution][$discipline][$collection][$year][$month][$day] += $data['count'];

				}

			}

		}

		file_put_contents($file,json_encode($file_data));

	}


	$institutions_count = count($institutions2);
	if($institutions_count>0)
		alert('info','Extracted information about '.$institutions_count.' institutions from '.$file_count.' files');


	function sort_months($x,$y){
		static $months_names = [
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December',
		];
		$left = array_search($x,$months_names);
		$right = array_search($y,$months_names);
		return $left === $right ? 0 : ($left < $right ? -1 : 1);
	}

	$i=0;
	$institutions4 = [];//list of institutions and their IDs
	foreach($institutions2 as $institution => &$discipline_data){

		foreach($discipline_data as $discipline => &$collection_data)

			foreach($collection_data as $collection => &$data){

				ksort($data);

				$months = [];
				$days = [];
				foreach($data as $year => $month_data){

					uksort($month_data,'sort_months');

					$months[$year] = [[],[]];
					$days[$year] = [];

					foreach($month_data as $month => $day_data){

						$days[$year][$month] = [[],[]];

						$month_sum = 0;
						$keys = array_keys($day_data);

						array_multisort($keys, SORT_NATURAL | SORT_FLAG_CASE, $day_data);

						foreach($day_data as $day => $day_sum){

							$days[$year][$month][0][] = $day;
							$days[$year][$month][1][] = $day_sum;

							$month_sum += $day_sum;

						}

						$months[$year][0][] = $month;
						$months[$year][1][] = $month_sum;

					}

				}

				$data = [$months,$days];

			}

		$institutions4[$i]=$institution;
		file_put_contents($institutions2_dir.$i.'.json',json_encode($discipline_data));
		$i++;

	}

	file_put_contents(WORKING_LOCATION.'institutions_id.json',json_encode($institutions4));
	file_put_contents(WORKING_LOCATION.'institutions.json',json_encode($institutions3));

}
