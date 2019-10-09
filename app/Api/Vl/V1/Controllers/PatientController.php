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
            return "facilitycode"; 
        }
        else{
            return "DHIScode";
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

        $art = $this->get_art_total($division, $type, $year, $div, $month, $year2, $month2);

        return [
            'unique' => $data,
            'art' => $art['art'],
            'as_at' => $art['as_at'],
        ];

		// $data = collect($data);

		// return $data;
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

    private function get_art_total($division, $type, $year, $div, $month=0, $year2=0, $month2=0)
    {
        extract($this->get_date($year, $month, $year2, $month2));       

        $col = ['', 'county', 'subcounty_id', 'partner', 'view_facilitys.id'];

        $row = DB::table('hcm.m_art')
            ->join('hcm.view_facilitys', 'view_facilitys.id', '=', 'm_art.facility')
            ->join('hcm.periods', 'periods.id', '=', 'm_art.period_id')
            ->selectRaw('SUM(current_total) AS current_total ')
            ->when($division, function($query) use($division, $div, $col){
                return $query->where($col[$division], $div[0]);
            })
            ->where(['year' => $y, 'month' => $m])
            ->first();

        return ['art' => $row->current_total, 'as_at' => $d];
    }


    private function get_pmtct_total($division, $type, $year, $div, $month=0, $year2=0, $month2=0)
    {
        extract($this->get_date($year, $month, $year2, $month2)); 

        $col = ['', 'county', 'subcounty_id', 'partner', 'view_facilitys.id'];

        $row = DB::table('hcm.m_pmtct')
            ->join('hcm.view_facilitys', 'view_facilitys.id', '=', 'm_pmtct.facility')
            ->join('hcm.periods', 'periods.id', '=', 'm_pmtct.period_id')
            // ->selectRaw('SUM(COALESCE(on_haart_anc) + COALESCE(start_art_anc) + COALESCE(start_art_lnd) + COALESCE(start_art_pnc) +  COALESCE(start_art_pnc_6m)) AS pmtct ')
            ->selectRaw("SUM(on_haart_anc) AS on_haart_anc, SUM(start_art_anc) AS anc, SUM(start_art_lnd) AS lnd, SUM(start_art_pnc) AS pnc, SUM(start_art_pnc_6m) AS pnc_6m")
            ->when($division, function($query) use($division, $div, $col){
                return $query->where($col[$division], $div[0]);
            })
            ->where(['year' => $y, 'month' => $m])
            ->first();

        $pmtct = $row->on_haart_anc + $row->anc + $row->lnd + $row->pnc + $row->pnc_6m;

        return ['pmtct' => $pmtct, 'as_at' => $d];
    }

    private function get_date($year, $month, $year2, $month2)
    {
        if($year2){
            $y = $year2;
            $m = $month2;
        }
        else{
            $y = $year;
            $m = $month;
            if(!$m) $m = 12;
        }
        $d = $y . '-' . $m . '-15';

        if(strtotime('now') < strtotime($d)){
            $day_of_month = date('j');
            $y = date('Y', strtotime("-{$day_of_month} days -1 month"));
            $m = date('m', strtotime("-{$day_of_month} days -1 month"));
        }

        $d = $y . '-' . $m . '-01';     
        $d = date('Y-m-d', strtotime($d . ' +1month -1day'));  

        return ['d' => $d, 'y' => $y, 'm' => $m];
    }

    public function get_results($site, $patientID){
    	$key = $this->set_key($site);
    	$query = $this->patient_query();

    	$data = DB::connection('national')
        ->table('viralsample_complete_view')
        ->join('view_facilitys', 'view_facilitys.id', '=', 'viralsample_complete_view.facility_id')
        ->leftJoin('labs', 'labs.id', '=', 'viralsample_complete_view.lab_id')
		->select(DB::raw($query))
		->where('patient', $patientID)
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



    public function national_art($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        return $this->get_art_total(0, $type, $year, [0, ''], $month, $year2, $month2); 
    }

    public function county_art($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_county($county);
        return $this->get_art_total(1, $type, $year, $div, $month, $year2, $month2);
    }

    public function subcounty_art($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_subcounty($subcounty);
        return $this->get_art_total(2, $type, $year, $div, $month, $year2, $month2);
    }

    public function facility_art($site, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_site($site);
        return $this->get_art_total(4, $type, $year, $div, $month, $year2, $month2);
    }

    public function partner_art($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = [$partner, 'partner'];
        return $this->get_art_total(3, $type, $year, $div, $month, $year2, $month2); 
    }



    public function national_pmtct($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        return $this->get_pmtct_total(0, $type, $year, [0, ''], $month, $year2, $month2); 
    }

    public function county_pmtct($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_county($county);
        return $this->get_pmtct_total(1, $type, $year, $div, $month, $year2, $month2);
    }

    public function subcounty_pmtct($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_subcounty($subcounty);
        return $this->get_pmtct_total(2, $type, $year, $div, $month, $year2, $month2);
    }

    public function facility_pmtct($site, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = $this->set_site($site);
        return $this->get_pmtct_total(4, $type, $year, $div, $month, $year2, $month2);
    }

    public function partner_pmtct($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
        $div = [$partner, 'partner'];
        return $this->get_pmtct_total(3, $type, $year, $div, $month, $year2, $month2); 
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
