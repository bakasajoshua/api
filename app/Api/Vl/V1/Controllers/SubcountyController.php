<?php

namespace App\Api\Vl\V1\Controllers;

use App\District;
use App\Api\Vl\V1\Controllers\BaseController;

use DB;

class SubcountyController extends BaseController
{
    //

	private function get_when_callback($subcounty, $key)
	{
		return function($query) use ($subcounty, $key){
				if($subcounty != "0" || $subcounty != 0){
					return $query->where($key, $subcounty);
				}					
			};
	}

    

	private function set_key($subcounty){
    	if(is_numeric($subcounty)){
			return "districts.SubCountyMFLCode"; 
		}
		else{
			return "districts.SubCountyDHISCode";
		}
    }

    public function subcounties(){
    	return DB::table('districts')->orderBy('ID')->get();
    }

    public function info($subcounty){
    	return DB::table('districts')->where('ID', $subcounty)->orWhere('SubCountyDHISCode', $subcounty)->orWhere('SubCountyMFLCode', $subcounty)->get();
    }

    public function summary($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->subcounty_string . $this->summary_query();

		$key = $this->set_key($subcounty);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_subcounty_summary')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_subcounty_summary')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->get();

			$a = "Q" . $month;
			$b = $this->quarter_description($month);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Quarter'] = $a;
				$data[$i]['Period'] = $b;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
		}

		// For Multiple Months across years
		else if($type == 5){

			if($year > $year2) return $this->pass_error('From year is greater');
			if($year == $year2 && $month >= $month2) return $this->pass_error('From month is greater');

			$q = $this->multiple_year($year, $month, $year2, $month2);
			// return $this->pass_error($q);

			if($year == $year2 && $month < $month2){
				$d = DB::table('vl_subcounty_summary')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_summary.subcounty')
				->where('year', $year)
				->where('tat4', '!=', 0)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_subcounty_summary')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_summary.subcounty')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->where('tat4', '!=', 0)
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode')
				->get();

				
			}
			$desc = $this->describe_multiple($year, $month, $year2, $month2);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Period'] = $desc;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
			
		}

		// Else an invalid type has been specified
		else{
			return $this->invalid_type($type);
		}

		
		return $this->check_data($data);

	}

	public function regimen($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->subcounty_string . $this->regimen_query();

		$key = $this->set_key($subcounty);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_subcounty_regimen')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_regimen.subcounty')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_subcounty_regimen.regimen')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('viralprophylaxis.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_subcounty_regimen')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_regimen.subcounty')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_subcounty_regimen.regimen')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('viralprophylaxis.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_subcounty_regimen')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_regimen.subcounty')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_subcounty_regimen.regimen')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('viralprophylaxis.name')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_subcounty_regimen')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_regimen.subcounty')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_subcounty_regimen.regimen')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('viralprophylaxis.name')
			->get();

			$a = "Q" . $month;
			$b = $this->quarter_description($month);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Quarter'] = $a;
				$data[$i]['Period'] = $b;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
		}

		// For Multiple Months across years
		else if($type == 5){

			if($year > $year2) return $this->pass_error('From year is greater');
			if($year == $year2 && $month >= $month2) return $this->pass_error('From month is greater');

			$q = $this->multiple_year($year, $month, $year2, $month2);
			// return $this->pass_error($q);

			if($year == $year2 && $month < $month2){
				$d = DB::table('vl_subcounty_regimen')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_regimen.subcounty')
				->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_subcounty_regimen.regimen')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
				->groupBy('viralprophylaxis.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_subcounty_regimen')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_regimen.subcounty')
				->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_subcounty_regimen.regimen')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode')
				->groupBy('viralprophylaxis.name')
				->get();

				
			}
			$desc = $this->describe_multiple($year, $month, $year2, $month2);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Period'] = $desc;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
			
		}

		// Else an invalid type has been specified
		else{
			return $this->invalid_type($type);
		}

		
		return $this->check_data($data);

	}

	public function gender($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->subcounty_string . $this->gender_query();

		$key = $this->set_key($subcounty);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_subcounty_gender')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_gender.subcounty')
			->leftJoin('gender', 'gender.ID', '=', 'vl_subcounty_gender.gender')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('gender.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_subcounty_gender')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_gender.subcounty')
			->leftJoin('gender', 'gender.ID', '=', 'vl_subcounty_gender.gender')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('gender.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_subcounty_gender')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_gender.subcounty')
			->leftJoin('gender', 'gender.ID', '=', 'vl_subcounty_gender.gender')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('gender.name')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_subcounty_gender')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_gender.subcounty')
			->leftJoin('gender', 'gender.ID', '=', 'vl_subcounty_gender.gender')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('gender.name')
			->get();

			$a = "Q" . $month;
			$b = $this->quarter_description($month);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Quarter'] = $a;
				$data[$i]['Period'] = $b;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
		}

		// For Multiple Months across years
		else if($type == 5){

			if($year > $year2) return $this->pass_error('From year is greater');
			if($year == $year2 && $month >= $month2) return $this->pass_error('From month is greater');

			$q = $this->multiple_year($year, $month, $year2, $month2);
			// return $this->pass_error($q);

			if($year == $year2 && $month < $month2){
				$d = DB::table('vl_subcounty_gender')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_gender.subcounty')
				->leftJoin('gender', 'gender.ID', '=', 'vl_subcounty_gender.gender')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
				->groupBy('gender.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_subcounty_gender')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_gender.subcounty')
				->leftJoin('gender', 'gender.ID', '=', 'vl_subcounty_gender.gender')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode')
				->groupBy('gender.name')
				->get();

				
			}
			$desc = $this->describe_multiple($year, $month, $year2, $month2);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Period'] = $desc;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
			
		}

		// Else an invalid type has been specified
		else{
			return $this->invalid_type($type);
		}

		
		return $this->check_data($data);

	}

	public function age($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->subcounty_string . $this->age_query();

		$key = $this->set_key($subcounty);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_subcounty_age')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_age.subcounty')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_subcounty_age.age')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('agecategory.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_subcounty_age')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_age.subcounty')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_subcounty_age.age')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('agecategory.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_subcounty_age')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_age.subcounty')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_subcounty_age.age')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('agecategory.name')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_subcounty_age')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_age.subcounty')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_subcounty_age.age')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
			->groupBy('agecategory.name')
			->get();

			$a = "Q" . $month;
			$b = $this->quarter_description($month);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Quarter'] = $a;
				$data[$i]['Period'] = $b;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
		}

		// For Multiple Months across years
		else if($type == 5){

			if($year > $year2) return $this->pass_error('From year is greater');
			if($year == $year2 && $month >= $month2) return $this->pass_error('From month is greater');

			$q = $this->multiple_year($year, $month, $year2, $month2);
			// return $this->pass_error($q);

			if($year == $year2 && $month < $month2){
				$d = DB::table('vl_subcounty_age')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_age.subcounty')
				->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_subcounty_age.age')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode', 'year')
				->groupBy('agecategory.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_subcounty_age')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'vl_subcounty_age.subcounty')
				->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_subcounty_age.age')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('districts.ID', 'districts.name', 'districts.subcountyDHISCode', 'districts.subcountyMFLCode')
				->groupBy('agecategory.name')
				->get();

				
			}
$desc = $this->describe_multiple($year, $month, $year2, $month2);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Period'] = $desc;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
			
		}

		// Else an invalid type has been specified
		else{
			return $this->invalid_type($type);
		}

		
		return $this->check_data($data);

	}

	public function subcounty_sites($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->site_string . $this->summary_query();

		$key = $this->set_key($subcounty);		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
			->join('districts', 'districts.ID', '=', 'facilitys.district')
			->where('year', $year)
			->where($key, $subcounty)
			->orderBy('alltests')
			->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_site_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
			->join('districts', 'districts.ID', '=', 'facilitys.district')
			->where('year', $year)
			->where($key, $subcounty)
			->orderBy('facilitys.ID')
			->orderBy('vl_site_summary.month')
			->groupBy('month')
			->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_site_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
			->join('districts', 'districts.ID', '=', 'facilitys.district')
			->where('year', $year)
			->where($key, $subcounty)
			->orderBy('alltests')
			->where('month', $month)
			->groupBy('month')
			->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
			->join('districts', 'districts.ID', '=', 'facilitys.district')
			->where('year', $year)
			->where($key, $subcounty)
			->orderBy('alltests')
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
			->get();
			
			$a = "Q" . $month;
			$b = $this->quarter_description($month);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Quarter'] = $a;
				$data[$i]['Period'] = $b;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
		}

		// For Multiple Months across years
		else if($type == 5){

			if($year > $year2) return $this->pass_error('From year is greater');
			if($year == $year2 && $month >= $month2) return $this->pass_error('From month is greater');

			$q = $this->multiple_year($year, $month, $year2, $month2);
			// return $this->pass_error($q);

			if($year == $year2 && $month < $month2){
				$d = DB::table('vl_site_summary')
				->select('year', DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
				->join('districts', 'districts.ID', '=', 'facilitys.district')
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->where($key, $subcounty)
				->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
				->orderBy('alltests')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_site_summary')
				->select( DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
				->join('districts', 'districts.ID', '=', 'facilitys.district')
				->whereRaw($q)
				->where($key, $subcounty)
				->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode')
				->orderBy('alltests')
				->get();

				
			}
$desc = $this->describe_multiple($year, $month, $year2, $month2);

			for ($i=0; $i < sizeof($d); $i++) { 
				$data[$i]['Period'] = $desc;
				foreach ($d[$i] as $obj_prop => $ob_val) {
					$data[$i][$obj_prop] = $ob_val;
				}
			}
			
		}

		// Else an invalid type has been specified
		else{
			return $this->invalid_type($type);
		}

		
		return $this->check_data($data);

	}
}
