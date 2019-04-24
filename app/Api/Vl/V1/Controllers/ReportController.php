<?php

namespace App\Api\Vl\V1\Controllers;

use Illuminate\Http\Request;
use App\Api\Vl\V1\Controllers\BaseController;

use DB;

class ReportController extends BaseController
{
    //

    private function set_key($site){
    	if(is_numeric($site)){
			return "patients.FacilityMFLcode"; 
		}
		else{
			return "patients.FacilityDHIScode";
		}
    }

    private function set_county($county){
    	if(is_numeric($county)){
			return "countys.CountyMFLCode"; 
		}
		else{
			return "countys.CountyDHISCode";
		}
    }

    private function set_subcounty($subcounty){
    	if(is_numeric($subcounty)){
			return "districts.SubCountyMFLCode"; 
		}
		else{
			return "districts.SubCountyDHISCode";
		}
    }

    public function nation_report($year=null, $month=null){
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = $this->report_string;

    	$data = DB::table('patients')
		->select(DB::raw($raw))
		->orderBy('FacilityMFLcode', 'desc')
		->groupBy('FacilityMFLcode', 'patientID')
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', '>', 1000)
		->where('receivedstatus', '!=', 2)
		->get();

		return $this->format_return($data);
    }


    public function county_report($county, $year=null, $month=null){
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = $this->report_string;
    	$key = $this->set_county($county);

    	$data = DB::table('patients')
		->select(DB::raw($raw))
		->join('facilitys', 'facilitys.facilitycode', '=', 'patients.FacilityMFLcode')
		->join('districts', 'districts.ID', '=', 'facilitys.district')
		->join('countys', 'countys.ID', '=', 'districts.county')
		->orderBy('FacilityMFLcode', 'desc')
		->groupBy('FacilityMFLcode', 'patientID')
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', '>', 1000)
		->where($key, $county)
		->where('receivedstatus', '!=', 2)
		->get();

		return $this->format_return($data);
    }

    public function subcounty_report($subcounty, $year=null, $month=null){
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = $this->report_string;
    	$key = $this->set_subcounty($subcounty);

    	$data = DB::table('patients')
		->select(DB::raw($raw))
		->join('facilitys', 'facilitys.facilitycode', '=', 'patients.FacilityMFLcode')
		->join('districts', 'districts.ID', '=', 'facilitys.district')
		->orderBy('FacilityMFLcode', 'desc')
		->groupBy('FacilityMFLcode', 'patientID')
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', '>', 1000)
		->where($key, $subcounty)
		->where('receivedstatus', '!=', 2)
		->get();

		return $this->format_return($data);
    }

    public function partner_report($partner, $year=null, $month=null){
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = $this->report_string;

    	$data = DB::table('patients')
		->select(DB::raw($raw))
		->join('facilitys', 'facilitys.facilitycode', '=', 'patients.FacilityMFLcode')
		->orderBy('FacilityMFLcode', 'desc')
		->groupBy('FacilityMFLcode', 'patientID')
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', '>', 1000)
		->where('facilitys.partner', $partner)
		->where('receivedstatus', '!=', 2)
		->get();

		return $this->format_return($data);
    }

    public function site_report($site, $year=null, $month=null){
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = $this->report_string;
    	$key = $this->set_key($site);

    	$data = DB::table('patients')
		->select(DB::raw($raw))
		->orderBy('FacilityMFLcode', 'desc')
		->groupBy('FacilityMFLcode', 'patientID')
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', '>', 1000)
		->where($key, $site)
		->where('receivedstatus', '!=', 2)
		->get();

		return $this->format_return($data);
    }

    public function format_return(&$data){

    	if ($data->isEmpty()) { return $this->pass_error("No data found"); }
    	
    	$raw = $this->report_string;
    	$i = 0;
		$result = null;

		foreach ($data as $patient) {
			$d = null;

			$d = DB::table('patients')
			->select(DB::raw($raw ))
			->where('FacilityMFLcode', $patient->FacilityMFLcode)
			->where('patientID', $patient->patientID)
			->where('result', '<', 1000)
			->where('datetested', '<', $patient->datetested)
			->where('receivedstatus', '!=', 2)
			->first();

			if($d != null){
				$result[$i]['FacilityMFLcode'] = $patient->FacilityMFLcode;
				$result[$i]['patientID'] = $patient->patientID;
				$result[$i]['not_suppressed_result'] = $patient->result;
				$result[$i]['not_suppressed_date'] = $patient->datetested;
				$result[$i]['suppressed_result'] = $d->result;
				$result[$i]['suppressed_date'] = $d->datetested;
				$i++;
			}

		}

		return $result;
    }

    public function suppression(){
    	ini_set("memory_limit", "-1");
    	// SELECT facility, rcategory, count(*) as totals
		// FROM
		// (SELECT v.ID, v.facility, v.rcategory 
		// FROM viralsamples v 
		// RIGHT JOIN 
		// (SELECT ID, patient, facility, max(datetested) as maxdate
		// FROM viralsamples
		// WHERE ( (year(datetested) = 2016 AND month(datetested) > 9) || (year(datetested) = 2017 AND month(datetested) < 10) ) 
		// AND flag=1 AND repeatt=0 AND rcategory between 1 AND 4 
		// AND justification != 10 AND facility != 7148
		// GROUP BY patient, facility) gv 
		// ON v.ID=gv.ID) tb
		// GROUP BY facility, rcategory 
		// ORDER BY facility, rcategory;
		

    	// $r = $this->current_range();

    	// $year = $r[0];
    	// $prev_year = $r[1];
    	// $month = $r[2];
    	// $prev_month = $r[3];

    	$sql = 'SELECT facility, rcategory, count(*) as totals ';
		$sql .= 'FROM ';
		$sql .= '(SELECT v.ID, v.patient, v.facility, v.rcategory, v.result ';
		$sql .= 'FROM viralsamples v ';
		$sql .= 'RIGHT JOIN ';
		$sql .= '(SELECT ID, patient, facility, result ';
		$sql .= 'FROM viralsamples ';
		$sql .= 'WHERE year(datetested) = ?  ';
		$sql .= 'AND flag=1 AND repeatt=0 AND rcategory between 1 and 4 ';
		$sql .= 'AND justification != 10 and facility != 7148 ';
		$sql .= 'GROUP BY patient, facility) gv ';
		$sql .= 'ON v.ID=gv.ID) tb ';
		$sql .= 'GROUP BY facility, rcategory ';
		$sql .= 'ORDER BY facility, rcategory ';

		$sql = "SELECT v.ID, v.patient, v.facility, v.rcategory, v.result";
		$sql .= 'FROM viralsamples v ';
		$sql .= 'WHERE year(datetested) = 2017 ';

    	$sql = 'SELECT facility, rcategory, count(*) as totals ';
		$sql .= 'FROM ';
		$sql .= '(SELECT v.ID, v.facility, v.rcategory ';
		$sql .= 'FROM viralsamples v ';
		$sql .= 'RIGHT JOIN ';
		$sql .= '(SELECT ID, patient, facility, max(datetested) as maxdate ';
		$sql .= 'FROM viralsamples ';
		$sql .= 'WHERE ( (year(datetested) = ? and month(datetested) > ?) || (year(datetested) = ? and month(datetested) < ?) ) ';
		$sql .= 'AND flag=1 AND repeatt=0 AND rcategory between 1 and 4 ';
		$sql .= 'AND justification != 10 and facility != 7148 ';
		$sql .= 'GROUP BY patient, facility) gv ';
		$sql .= 'ON v.ID=gv.ID) tb ';
		$sql .= 'GROUP BY facility, rcategory ';
		$sql .= 'ORDER BY facility, rcategory ';

		$data = DB::connection('vl')->select($sql, [2017]);

		return $data;
    }

    public function new_report(){
    	ini_set("memory_limit", "-1");

    	$sql = "select count(*) as `tests`, facility, patient, labs.name as lab
				from viralsamples 
				JOIN labs on viralsamples.labtestedin=labs.ID ";

        $sql .= " where viralsamples.rcategory between 1 and 4 ";
        $sql .= " and viralsamples.flag=1 and viralsamples.repeatt=0 ";
		$sql .= " and patient != '' and patient != 'null' and patient is not null and facility != 7148 ";
		$sql .= " and year(datetested) = 2017 ";
		$sql .= " group by facility, patient ";
		$sql .= " having tests > 1 ";

		$get_patients = "SELECT datetested, month(datetested) AS test_month, result, justification 
							FROM viralsamples 
							WHERE viralsamples.rcategory BETWEEN 1 and 4 
							and viralsamples.flag=1 and viralsamples.repeatt=0 
							and year(datetested) = 2017 
							and patient = ? and facility = ? ";

		$data = DB::connection('vl')->select($sql);

		$return_data = null;
		$i = 0;

		foreach ($data as $key => $value) {
			$results = DB::connection('vl')->select($get_patients, [$value['patient'], $value['facility']]);
			// $results = collect($results);

			$first = true;
			$max = $min = 0;
			$max_date = $min_date = null;
			$max_justification = $min_justification = 0;

			foreach ($results as $key2 => $value2) {
				if($first){
					$max = $min = $this->check_int($value2->result);
					$max_date = $min_date = $value2->datetested;
					$max_justification = $min_justification = $value2->justification;
					$first = false;
					continue;
				}

				if($value2->result > $max){
					$max = $value2->result;
					$max_justification = $value2->justification;
					$max_date = $value2->datetested;
				}

				if($value2->result < $min){
					$min = $value2->result;
					$min_justification = $value2->justification;
					$min_date = $value2->datetested;
				}
			}

			if(($max - $min) > 500){
				$return_data['lab'] = $value->lab;
				$return_data['facility'] = $value->facility;
				$return_data['patient'] = $value->patient;

				$return_data['max_datetested'] = $maxdate;
				$return_data['min_datetested'] = $mindate;

				$return_data['max_result'] = $max;
				$return_data['min_result'] = $min;

				$return_data['max_justification'] = $max_justification;
				$return_data['min_justification'] = $min_justification;
				$i++;

			}			


		}

    }

    private function check_int($var){
    	if(is_numeric($var)){
    		return $var;
    	}
    	else{
    		return 0;
    	}
    }




}