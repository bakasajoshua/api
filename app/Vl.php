<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\VlReport;

use DB;
use Excel;

class Vl extends Model
{
    //

    public function new_report($year = null){
    	echo "\n Method start at " . date('d/m/Y h:i:s a', time());
    	if($year==null){
    		$year = Date('Y');
    	}

    	ini_set("memory_limit", "-1");

    	$sql = "SELECT count(*) AS `tests`, facility, patient, labs.name AS lab, view_facilitys.name AS facility_name
				FROM viralsamples 
				JOIN labs on viralsamples.labtestedin=labs.ID 
				LEFT JOIN view_facilitys on viralsamples.facility=view_facilitys.ID ";

        $sql .= " where viralsamples.rcategory between 1 AND 4 ";
        $sql .= " AND viralsamples.flag=1 AND viralsamples.repeatt=0 ";
		$sql .= " AND patient != '' AND patient != 'null' AND patient is not null AND facility != 7148 ";
		$sql .= " AND year(datetested) = {$year} ";
		$sql .= " group by facility, patient ";
		$sql .= " having tests > 1 ";

		$get_patients = "SELECT datetested, result, justification 
							FROM viralsamples 
							WHERE viralsamples.rcategory BETWEEN 1 AND 4 
							AND viralsamples.flag=1 AND viralsamples.repeatt=0 
							AND year(datetested) = {$year} 
							AND patient = ? AND facility = ? ";

		$data = DB::connection('vl')->select($sql);

		$return_data = null;
		$i = 0;

		echo "\n Begin looping at " . date('d/m/Y h:i:s a', time());

		foreach ($data as $key => $value) {
			$results = DB::connection('vl')->select($get_patients, [$value->patient, $value->facility]);
			// $results = collect($results);

			$first = true;
			$max = $min = 0;
			$max_date = $min_date = null;
			$max_justification = $min_justification = 0;

			foreach ($results as $key2 => $value2) {
				$test_val = $this->check_int($value2->result);
				if($first){
					$max = $min = $test_val;
					$max_date = $min_date = $value2->datetested;
					$max_justification = $min_justification = $value2->justification;
					$first = false;
					continue;
				}

				if($test_val > $max){
					$max = $test_val;
					$max_justification = $value2->justification;
					$max_date = $value2->datetested;
				}

				if($test_val < $min){
					$min = $test_val;
					$min_justification = $value2->justification;
					$min_date = $value2->datetested;
				}
			}

			if(($max - $min) > 500){
				// $return_data[$i]['lab'] = $value->lab;
				// $return_data[$i]['facility'] = $value->facility;
				// $return_data[$i]['patient'] = $value->patient;
				// $return_data[$i]['viral_difference'] = ($max - $min);

				// $return_data[$i]['max_datetested'] = $max_date;
				// $return_data[$i]['min_datetested'] = $min_date;

				// $return_data[$i]['max_result'] = $max;
				// $return_data[$i]['min_result'] = $min;

				// $return_data[$i]['max_justification'] = $max_justification;
				// $return_data[$i]['min_justification'] = $min_justification;
				// $i++;

				$return_data[] = array(
					'lab' => $value->lab,
					'facility_name' => $value->facility_name,
					'facility' => $value->facility,
					'patient' => $value->patient,
					'viral_difference' => ($max - $min),
					'max_datetested' => $max_date,
					'min_datetested' => $min_date,
					'max_result' => $max,
					'min_result' => $min,
					'max_justification' => $max_justification,
					'min_justification' => $min_justification
				);
				$i++;
			}
		}

		echo "\n Complete looping at " . date('d/m/Y h:i:s a', time());

		Excel::create('Vl_Standard_Report', function($excel) use($return_data)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($return_data) {

		        $sheet->fromArray($return_data);

		    });

		})->store('csv');

		echo "\n Complete method at " . date('d/m/Y h:i:s a', time());

    }

    public function newer_report($year = null){
    	echo "\n Method start at " . date('d/m/Y h:i:s a', time());
    	if($year==null){
    		$year = Date('Y');
    	}

    	ini_set("memory_limit", "-1");

    	$sql = "select count(*) as `tests`, facility, patient, labs.name as lab
				from viralsamples 
				JOIN labs on viralsamples.labtestedin=labs.ID ";

        $sql .= " where viralsamples.rcategory between 1 AND 4 ";
        $sql .= " AND viralsamples.flag=1 AND viralsamples.repeatt=0 ";
		$sql .= " AND patient != '' AND patient != 'null' AND patient is not null AND facility != 7148 ";
		$sql .= " AND year(datetested) = {$year} ";
		$sql .= " group by facility, patient ";
		$sql .= " having tests > 1 ";

		$get_patients = "SELECT datetested, month(datetested) AS test_month, result, justification 
							FROM viralsamples 
							WHERE viralsamples.rcategory BETWEEN 1 AND 4 
							AND viralsamples.flag=1 AND viralsamples.repeatt=0 
							AND year(datetested) = 2017 
							AND patient = ? AND facility = ? ";

		$data = DB::connection('vl')->select($sql);

		$return_data = null;
		$i = 0;

		echo "\n Begin looping at " . date('d/m/Y h:i:s a', time());

		foreach ($data as $key => $value) {
			$results = DB::connection('vl')->select($get_patients, [$value->patient, $value->facility]);
			// $results = collect($results);

			$first = true;
			$max = $min = 0;
			$max_date = $min_date = null;
			$max_justification = $min_justification = 0;

			foreach ($results as $key2 => $value2) {
				$test_val = $this->check_int($value2->result);
				if($first){
					$max = $min = $test_val;
					$max_date = $min_date = $value2->datetested;
					$max_justification = $min_justification = $value2->justification;
					$first = false;
					continue;
				}

				if($test_val > $max){
					$max = $test_val;
					$max_justification = $value2->justification;
					$max_date = $value2->datetested;
				}

				if($test_val < $min){
					$min = $test_val;
					$min_justification = $value2->justification;
					$min_date = $value2->datetested;
				}
			}

			if(($max - $min) > 500){
				// $return_data[$i]['lab'] = $value->lab;
				// $return_data[$i]['facility'] = $value->facility;
				// $return_data[$i]['patient'] = $value->patient;
				// $return_data[$i]['viral_difference'] = ($max - $min);

				// $return_data[$i]['max_datetested'] = $max_date;
				// $return_data[$i]['min_datetested'] = $min_date;

				// $return_data[$i]['max_result'] = $max;
				// $return_data[$i]['min_result'] = $min;

				// $return_data[$i]['max_justification'] = $max_justification;
				// $return_data[$i]['min_justification'] = $min_justification;
				// $i++;

				$return_data[] = array(
					'lab' => $value->lab,
					'facility' => $value->facility,
					'patient' => $value->patient,
					'viral_difference' => ($max - $min),
					'max_datetested' => $max_date,
					'min_datetested' => $min_date,
					'max_result' => $max,
					'min_result' => $min,
					'max_justification' => $max_justification,
					'min_justification' => $min_justification
				);
				$i++;
			}
		}

		echo "\n Complete looping at " . date('d/m/Y h:i:s a', time());

		Excel::create('Vl_Standard_Report', function($excel) use($return_data)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($return_data) {

		        $sheet->fromArray($return_data);

		    });

		})->store('csv');

		echo "\n Complete method at " . date('d/m/Y h:i:s a', time());

    }

    public function update_art()
    {
    	$file = public_path('fac.csv');
    	$data = Excel::load($file, function($reader){})->get();

    	$query = '';

    	foreach ($data as $key => $value) {
    		DB::table('facilitys')->where('facilitycode', $value->facility)->update(['totalartsep17' => $value->current]);
    	}

    	echo $query;
    }

    private function check_int($var){
    	if(is_numeric($var)){
    		return  (int) $var;
    	}
    	else{
    		return 0;
    	}
    }

    public function send_report(){
    	$mail_array = array('joelkith@gmail.com', 'tngugi@gmail.com', 'jbatuka@usaid.gov');
    	$up = new VlReport;
    	Mail::to($mail_array)->send($up);
    }
}
