<?php

namespace App\Api\Vl\V1\Controllers;

use App\National;
use App\Api\Vl\V1\Controllers\BaseController;

use Carbon\Carbon;

use DB;

class PatientController extends BaseController
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

    private function set_site($site){
        $data = DB::table('facilitys')->select('ID')->where('facilitycode', $site)->orWhere('DHISCode', $site)->first();
		return [$data->ID, 'facility'];
    }

    private function set_county($county){
		$data = DB::table('countys')->select('ID')->where('CountyMFLCode', $county)->orWhere('CountyDHISCode', $county)->first();
		return [$data->ID, 'county'];
    }

    private function set_subcounty($subcounty){
		$data = DB::table('districts')->select('ID')->where('SubCountyMFLCode', $subcounty)->orWhere('SubCountyDHISCode', $subcounty)->first();
		return [$data->ID, 'subcounty'];
    }

    private function store_raw($year){
    	$sql = 'select count(gp.tests) as totals, gp.tests
				from (
				select count(*) as `tests`, FacilityMFLcode, patientID
				from patients 
				where year(datetested) = {$year}
				group by FacilityMFLcode, patientID
				) gp
				group by gp.tests
				order by totals desc';
    }

    private function get_patients($division, $type, $year, $div, $month=0, $year2=0, $month2=0)
    {
    	if($type == 4) if($month < 1 || $month > 4) return $this->invalid_quarter($month);

        $daterange = $this->set_date_range($type, $year, $month, $year2, $month2);

        if(is_array($daterange)) return $this->pass_error($daterange['error']);


        $sql = "select count(gp.tests) as totals, gp.tests
                from (
                select count(*) as `tests`, patient_id
                from viralsample_synch_view ";

        $sql .= " where rcategory IN (1, 2, 3, 4) and flag=1 and repeatt=0 and facility != 7148 ";
        $sql .= " and patient != '' and patient != 'null' and patient is not null ";
        $sql .= " and {$daterange} ";

        if($division != 0) $sql .= " and {$div[1]} = {$div[0]} ";

        $sql .= " group by patient_id) gp ";
        $sql .= " group by gp.tests order by tests asc ";

        // return ['sql' => $sql];

        // $sql = "call proc_get_vl_longitudinal_tracking({$division}, {$type}, '{$div[1]}', {$div[0]}, {$year}, {$month}, {$year2}, {$month2})";

		$data = DB::connection('national')->select($sql);

		$data = collect($data);

		return $data;

		// return $this->return_patients($data);

    }

    private function get_current_suppression($division, $type, $year, $div, $month=0, $year2=0, $month2=0)
    {
        if($type == 4) if($month < 1 || $month > 4) return $this->invalid_quarter($month);

        $daterange = $this->set_date_range($type, $year, $month, $year2, $month2);

        if(is_array($daterange)) return $this->pass_error($daterange['error']);

        $sql = 'SELECT rcategory, count(*) as totals ';
        $sql .= 'FROM ';
        $sql .= '(SELECT v.id, v.rcategory ';
        $sql .= 'FROM viralsample_synch_view v ';
        $sql .= 'RIGHT JOIN ';
        $sql .= '(SELECT id, patient_id, max(datetested) as maxdate ';
        $sql .= 'FROM viralsample_synch_view ';
        $sql .= "WHERE patient != '' AND patient != 'null' AND patient is not null ";
        if($division != 0) $sql .= " and {$div[1]} = {$div[0]} ";
        $sql .= " and {$daterange} ";
        $sql .= 'AND flag=1 AND repeatt=0 AND rcategory in (1, 2, 3, 4) ';
        $sql .= 'AND justification != 10 AND facility_id != 7148 ';
        $sql .= 'GROUP BY patient_id) gv ';
        $sql .= 'ON v.id=gv.id) tb ';
        $sql .= 'GROUP BY rcategory ';
        $sql .= 'ORDER BY rcategory ';

        $data = DB::connection('national')->select($sql);

        $data = collect($data);

        return [
            'rcategory1' => $data->where('rcategory', 1)->first()->totals ?? 0,
            'rcategory2' => $data->where('rcategory', 2)->first()->totals ?? 0,
            'rcategory3' => $data->where('rcategory', 3)->first()->totals ?? 0,
            'rcategory4' => $data->where('rcategory', 4)->first()->totals ?? 0,
        ];
    }

    public function get_results($site, $patientID){
    	$key = $this->set_key($site);
    	$query = $this->patient_query();

    	$data = DB::table('patients')
		->select(DB::raw($query))
		->join('facilitys', 'facilitys.facilitycode', '=', 'patients.FacilityMFLcode')
		->join('partners', 'partners.ID', '=', 'facilitys.partner')
		->join('districts', 'districts.ID', '=', 'facilitys.district')
		->join('countys', 'countys.ID', '=', 'districts.county')
		->leftJoin('viralsampletypedetails', 'viralsampletypedetails.ID', '=', 'patients.SampleType')
		->leftJoin('viralrejectedreasons', 'viralrejectedreasons.ID', '=', 'patients.rejectedreason')
		->leftJoin('viraljustifications', 'viraljustifications.ID', '=', 'patients.Justification')
		->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'patients.Regimen')
		->leftJoin('labs', 'labs.ID', '=', 'patients.labtestedin')
		->leftJoin('receivedstatus', 'receivedstatus.ID', '=', 'patients.receivedstatus')
		->where('patientID', $patientID)
		->where($key, $site)
		->get();

		return $this->check_data($data);
    }

    public function national_viralloads($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	return $this->get_patients(0, $type, $year, [0, ''], $month, $year2, $month2); 
    }

    public function county_viralloads($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	$div = $this->set_county($county);
    	return $this->get_patients(1, $type, $year, $div, $month, $year2, $month2);
    }

    public function subcounty_viralloads($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	$div = $this->set_subcounty($subcounty);
    	return $this->get_patients(2, $type, $year, $div, $month, $year2, $month2);
    }

    public function facility_viralloads($site, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	$div = $this->set_site($site);
    	return $this->get_patients(4, $type, $year, $div, $month, $year2, $month2);
    }

    public function partner_viralloads($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	$div = [$partner, 'partner'];
    	return $this->get_patients(3, $type, $year, $div, $month, $year2, $month2); 
    }




    public function national_suppression($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        return $this->get_current_suppression(0, $type, $year, [0, ''], $month, $year2, $month2); 
    }

    public function county_suppression($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_county($county);
        return $this->get_current_suppression(1, $type, $year, $div, $month, $year2, $month2);
    }

    public function subcounty_suppression($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_subcounty($subcounty);
        return $this->get_current_suppression(2, $type, $year, $div, $month, $year2, $month2);
    }

    public function facility_suppression($site, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_site($site);
        return $this->get_current_suppression(4, $type, $year, $div, $month, $year2, $month2);
    }

    public function partner_suppression($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = [$partner, 'partner'];
        return $this->get_current_suppression(3, $type, $year, $div, $month, $year2, $month2); 
    }

    public function format_return(&$data=null){

		if ($data->isEmpty()) { return $this->pass_error("No data found"); }

    	$info = array(
    		"one" => 0,
    		"two" => 0,
    		"three" => 0,
    		"three_g" => 0,
    		"highest" => 0,
    		"total_patients" => 0,
    		"total_viralloads" => 0,
    		"avg_viralloads" => 0
    	);

    	$b = true;

    	foreach ($data as $key => $value) {
    		$v = $value->viralloads;
    		if($b){
    			$b = false;
    			$info['highest'] = $v;
    		}
    		$info["total_patients"]++;

    		$info["total_viralloads"] += $v;

    		switch ($v) {
    			case 1:
    				$info["one"]++;
    				break;
    			case 2:
    				$info["two"]++;
    				break;
    			case 3:
    				$info["three"]++;
    				break;
    			default:
    				$info["three_g"]++;
    				break;
    		}
    	}

    	$info["avg_viralloads"] = round(@(($info["total_viralloads"] / $info["total_patients"])), 2);

    	return $info;

    }

    private function return_patients($data){

    	if ($data->isEmpty()) { return $this->pass_error("No data found"); }

    	$info["one"] = 0;
    	$info["two"] = 0;
    	$info["three"] = 0;
    	$info["three_g"] = 0;
    	$info["total_patients"] = 0;
    	$info["total_tests"] = 0;

        $info['highest'] = $data->max('tests');
    	

    	foreach ($data as $key => $value) {
    		$tests = $value->tests;
    		$totals = $value->totals;

    		$info["total_patients"] += $totals;

    		$info["total_tests"] += ($tests * $totals);

    		switch ($tests) {
    			case 1:
    				$info["one"] += $totals;
    				break;
    			case 2:
    				$info["two"] += $totals;
    				break;
    			case 3:
    				$info["three"] += $totals;
    				break;
    			default:
    				$info["three_g"] += $totals;
    				break;
    		}
    	}

    	$info["avg_tests"] = round(@(($info["total_tests"] / $info["total_patients"])), 2);

    	return $info;
    }



 //    public function viralloads(){

	// 	$data = NULL;

	// 	$raw = $this->patient_string;


	// 	$data = DB::table('patients')
	// 	->select(DB::raw($raw))
	// 	->groupBy('patients.FacilityMFLcode', 'patientID')
	// 	->orderBy('viralloads', 'desc')
	// 	->havingRaw('COUNT(*) > 1')
	// 	->whereYear('datetested', '<', 2014)
	// 	->get();

		
	// 	return $data;

	// }

	public function viralloads(){

		$data = NULL;

		$raw = $this->patient_string;


		$data = DB::table('patients')
		->select(DB::raw($raw))
		->groupBy('patients.FacilityMFLcode', 'patientID')
		->orderBy('viralloads', 'desc')
		->havingRaw('COUNT(*) > 1')
		->whereDate('datetested', '>', '2014-10-01')
		->get();

		
		return $data;

	}



	public function test(){
		if(true) return $this->test2();
		
	}

	public function test2(){
		return redirect('api/vl/v1/patient/viralloads');
	}

    private function get_patients_old($division, $type, $year, $div, $month=null, $year2=null, $month2=null){

        $my_range;

        if($type == 4){
            if($month < 1 || $month > 4) return $this->invalid_quarter($month);
            
            $my_range = $this->set_quarters($year, $month);
        }

        if($type == 5){
            if($year > $year2) return $this->pass_error('From year is greater');
            if($year == $year2 && $month >= $month2) return $this->pass_error('From month is greater');

            $my_range = $this->set_date($year, $month, $year2, $month2);
        }

        if($type == 2 || $type > 5){
            return $this->invalid_type($type);
        }

        $sql = "select count(gp.tests) as totals, gp.tests
                from (
                select count(*) as `tests`, FacilityMFLcode, patientID
                from patients "; 

        if($division > 0 && $division < 4){
            $sql .= " join view_facilitys ON patients.FacilityMFLcode=view_facilitys.facilitycode ";
        }

        $sql .= " where receivedstatus!=2 ";

        switch ($type) {
            case 1:
                $sql .= " and year(datetested) = {$year} ";
                break;
            case 3:
                $sql .= " and year(datetested) = {$year} and month(datetested) = {$month} ";
                break;
            default:
                $sql .= " and datetested > '{$my_range[0]}' and datetested < '{$my_range[1]}' ";
                break;
        }

        if($division != 1){
            $sql .= " and {$div[1]} = {$div[0]} ";
        }

        $sql .= " group by FacilityMFLcode, patientID) gp ";
        $sql .= " group by gp.tests order by totals desc ";

        $data = DB::connection('vl')->select($sql);

        $data = collect($data);

        return $data;

        // return $this->return_patients($data);

    }

	


}
