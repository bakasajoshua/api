<?php

namespace App\Api\Eid\V1\Controllers;

use Illuminate\Http\Request;
use App\National;
use App\Api\Eid\V1\Controllers\BaseController;

use DB;

class PatientController extends BaseController
{
    //

    private function store_raw(){
    	$sql = 'select count(gp.tests) as totals, gp.tests
				from (
				select count(*) as `tests`, facility, patient
				from samples 
				where year(datetested) = {$year}
				and pcrtype in (1, 2, 3)
				and result between 1 and 2
				and age between 0.00001 and 24
                and repeatt=0
				group by facility, patient
				) gp
				group by gp.tests
				order by totals desc';
    }

    private function set_key($site){
    	if(is_numeric($site)){
			return "patients_eid.FacilityMFLcode"; 
		}
		else{
			return "patients_eid.FacilityDHIScode";
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

    private function get_patients($division, $type, $year, $div, $month=null, $year2=null, $month2=null)
    {
        if($type == 4) if($month < 1 || $month > 4) return $this->invalid_quarter($month);

        $daterange = $this->set_date_range($type, $year, $month, $year2, $month2);

        if(is_array($daterange)) return $this->pass_error($daterange['error']);


        $sql = "select count(gp.tests) as totals, gp.tests
                from (
                select count(*) as `tests`, patient_id
                from sample_synch_view ";

        $sql .= " where pcrtype=1 and result IN (1, 2) and age between 0.00001 and 24 ";
        $sql .= " and flag = 1 and repeatt = 0 and facility_id != 7148  ";
        $sql .= " and {$daterange} ";

        if($division != 0) $sql .= " and {$div[1]} = {$div[0]} ";

        $sql .= " group by patient_id) gp ";
        $sql .= " group by gp.tests order by tests asc ";


		$data = DB::connection('national')->select($sql);

		$data = collect($data);

		return $data;

		// return $this->return_patients($data);

    }

    private function get_patients_detailed($division, $type, $pcrtype, $age, $year, $div, $month=null, $year2=null, $month2=null)
    {
        if($type == 4) if($month < 1 || $month > 4) return $this->invalid_quarter($month);

        $daterange = $this->set_date_range($type, $year, $month, $year2, $month2);

        if(is_array($daterange)) return $this->pass_error($daterange['error']);


        $sql = "select count(gp.tests) as totals, gp.tests
                from (
                select count(*) as `tests`, patient_id
                from sample_synch_view ";

        $sql .= " where pcrtype=1 and result IN (1, 2) and age between 0.00001 and 24 ";
        $sql .= " and flag = 1 and repeatt = 0 and facility_id != 7148  ";
        $sql .= " and {$daterange} ";

        if($age != 0){
            $age_range = DB::table('age_bands')->where('ID', $age)->first();
            $sql .= " and age between {$age_range->lower} and {$age_range->upper} ";
        }

        if($pcrtype != 0) $sql .= " and pcrtype = {$pcrtype} ";

        if($division != 0) $sql .= " and {$div[1]} = {$div[0]} ";

        $sql .= " group by patient_id) gp ";
        $sql .= " group by gp.tests order by tests asc ";



        $data = DB::connection('national')->select($sql);

        $data = collect($data);

        return $data;

        // return $this->return_patients($data);

    }

    private function get_patients_details($division, $type, $pcrtype, $age_lower, $age_upper, $year, $div, $month=null, $year2=null, $month2=null)
    {
        if($type == 4) if($month < 1 || $month > 4) return $this->invalid_quarter($month);

        $daterange = $this->set_date_range($type, $year, $month, $year2, $month2);

        if(is_array($daterange)) return $this->pass_error($daterange['error']);


        $sql = "select count(gp.tests) as totals, gp.tests
                from (
                select count(*) as `tests`, patient_id
                from sample_synch_view ";

        $sql .= " where pcrtype=1 and result IN (1, 2) and age between 0.00001 and 24 ";
        $sql .= " and flag = 1 and repeatt = 0 and facility_id != 7148  ";
        $sql .= " and {$daterange} ";

        if($age_lower != 0 && $age_upper != 0) $sql .= " and age between {$age_lower} and {$age_upper} ";

        if($pcrtype != 0) $sql .= " and pcrtype = {$pcrtype} ";

        if($division != 0) $sql .= " and {$div[1]} = {$div[0]} ";

        $sql .= " group by patient_id) gp ";
        $sql .= " group by gp.tests order by tests asc ";


        $data = DB::connection('national')->select($sql);

        $data = collect($data);

        return $data;

        // return $this->return_patients($data);

    }

    public function get_results($site, $patientID){
    	$key = $this->set_key($site);
    	$query = $this->patient_query();

    	$data = DB::table('patients_eid')
		->select(DB::raw($query))
		->join('facilitys', 'facilitys.facilitycode', '=', 'patients_eid.FacilityMFLcode')
		->join('partners', 'partners.ID', '=', 'facilitys.partner')
		->join('districts', 'districts.ID', '=', 'facilitys.district')
		->join('countys', 'countys.ID', '=', 'districts.county')
		->leftJoin('prophylaxis AS mp', 'mp.ID', '=', 'patients_eid.motherregimen')
		->leftJoin('prophylaxis AS ip', 'ip.ID', '=', 'patients_eid.infantregimen')
		->leftJoin('entry_points', 'entry_points.ID', '=', 'patients_eid.entrypoint')
		->leftJoin('feedings', 'feedings.ID', '=', 'patients_eid.feedingtype')
		->leftJoin('results', 'results.ID', '=', 'patients_eid.result')
		->leftJoin('rejectedreasons', 'rejectedreasons.ID', '=', 'patients_eid.rejectedreason')
		->leftJoin('labs', 'labs.ID', '=', 'patients_eid.labtestedin')
		->leftJoin('receivedstatus', 'receivedstatus.ID', '=', 'patients_eid.receivedstatus')
		->where('patientID', $patientID)
		->where($key, $site)
		->get();

		return $this->check_data($data);
    }

    public function national_tests($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	return $this->get_patients(0, $type, $year, [0, 0], $month, $year2, $month2);
    }

    public function county_tests($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	$div = $this->set_county($county);
    	return $this->get_patients(1, $type, $year, $div, $month, $year2, $month2);
    }

    public function subcounty_tests($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	$div = $this->set_subcounty($subcounty);
    	return $this->get_patients(2, $type, $year, $div, $month, $year2, $month2);
    }

    public function facility_tests($site, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

    	$div = $this->set_site($site);
    	return $this->get_patients(4, $type, $year, $div, $month, $year2, $month2);
    }

    public function partner_tests($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
    	
    	$div = [$partner, 'view_facilitys.partner'];
    	return $this->get_patients(3, $type, $year, $div, $month, $year2, $month2);
    }



    public function national_tests2($type, $pcrtype, $age, $year, $month=NULL, $year2=NULL, $month2=NULL){
        return $this->get_patients_detailed(0, $type, $pcrtype, $age, $year, [0, 0], $month, $year2, $month2);
    }

    public function county_tests2($county, $type, $pcrtype, $age, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_county($county);
        return $this->get_patients_detailed(1, $type, $pcrtype, $age, $year, $div, $month, $year2, $month2);
    }

    public function subcounty_tests2($subcounty, $type, $pcrtype, $age, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_subcounty($subcounty);
        return $this->get_patients_detailed(2, $type, $pcrtype, $age, $year, $div, $month, $year2, $month2);
    }

    public function partner_tests2($partner, $type, $pcrtype, $age, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = [$partner, 'view_facilitys.partner'];
        return $this->get_patients_detailed(3, $type, $pcrtype, $age, $year, $div, $month, $year2, $month2);
    }

    public function facility_tests2($site, $type, $pcrtype, $age, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_site($site);
        return $this->get_patients_detailed(4, $type, $pcrtype, $age, $year, $div, $month, $year2, $month2);
    }



    public function national_tests3($type, $pcrtype, $age_lower, $age_upper, $year, $month=NULL, $year2=NULL, $month2=NULL){
        return $this->get_patients_details(0, $type, $pcrtype, $age_lower, $age_upper, $year, [0, 0], $month, $year2, $month2);
    }

    public function county_tests3($county, $type, $pcrtype, $age_lower, $age_upper, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_county($county);
        return $this->get_patients_details(1, $type, $pcrtype, $age_lower, $age_upper, $year, $div, $month, $year2, $month2);
    }

    public function subcounty_tests3($subcounty, $type, $pcrtype, $age_lower, $age_upper, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_subcounty($subcounty);
        return $this->get_patients_details(2, $type, $pcrtype, $age_lower, $age_upper, $year, $div, $month, $year2, $month2);
    }

    public function partner_tests3($partner, $type, $pcrtype, $age_lower, $age_upper, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = [$partner, 'view_facilitys.partner'];
        return $this->get_patients_details(3, $type, $pcrtype, $age_lower, $age_upper, $year, $div, $month, $year2, $month2);
    }

    public function facility_tests3($site, $type, $pcrtype, $age_lower, $age_upper, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_site($site);
        return $this->get_patients_details(4, $type, $pcrtype, $age_lower, $age_upper, $year, $div, $month, $year2, $month2);
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
    		"total_tests" => 0,
    		"avg_tests" => 0
    	);

    	$b = true;

    	foreach ($data as $key => $value) {
    		$v = $value->tests;
    		if($b){
    			$b = false;
    			$info['highest'] = $v;
    		}
    		$info["total_patients"]++;

    		$info["total_tests"] += $v;

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

    	$info["avg_tests"] = round(@(($info["total_tests"] / $info["total_patients"])), 2);

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

    


}