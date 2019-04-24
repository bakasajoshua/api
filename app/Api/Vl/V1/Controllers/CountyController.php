<?php

namespace App\Api\Vl\V1\Controllers;

use App\County;
use App\Api\Vl\V1\Controllers\BaseController;

use DB;

class CountyController extends BaseController
{
    //

	private function get_when_callback($county, $key)
	{
		return function($query) use ($county, $key){
				if($county != "0" || $county != 0){
					return $query->where($key, $county);
				}
					
			};
	}
    

    private function set_key($county){
    	if(is_numeric($county)){
			return "countys.CountyMFLCode"; 
		}
		else{
			return "countys.CountyDHISCode";
		}
    }

    public function counties(){
    	return DB::table('countys')->orderBy('ID')->get();
    }

    public function info($county){
    	return DB::table('countys')->where('ID', $county)->orWhere('CountyDHISCode', $county)->orWhere('CountyMFLCode', $county)->get();
    }

    

    public function summary($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->county_string . $this->summary_query();

		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_county_summary')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_county_summary')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
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
				$d = DB::table('vl_county_summary')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_summary.county')
				->where('year', $year)
				->where('tat4', '!=', 0)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_county_summary')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_summary.county')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
				->where('tat4', '!=', 0)
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode')
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

	public function regimen($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->county_string . $this->regimen_query();

		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_county_regimen')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_regimen.county')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_county_regimen.regimen')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->groupBy('viralprophylaxis.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_county_regimen')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_regimen.county')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_county_regimen.regimen')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->groupBy('viralprophylaxis.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_county_regimen')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_regimen.county')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_county_regimen.regimen')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
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

			$d = DB::table('vl_county_regimen')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_regimen.county')
			->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_county_regimen.regimen')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
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
				$d = DB::table('vl_county_regimen')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_regimen.county')
				->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_county_regimen.regimen')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->groupBy('viralprophylaxis.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_county_regimen')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_regimen.county')
				->leftJoin('viralprophylaxis', 'viralprophylaxis.ID', '=', 'vl_county_regimen.regimen')
				->when($county, $this->get_when_callback($county, $key))
				->whereRaw($q)
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode')
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

	public function gender($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->county_string . $this->gender_query();

		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_county_gender')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_gender.county')
			->leftJoin('gender', 'gender.ID', '=', 'vl_county_gender.gender')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->groupBy('gender.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_county_gender')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_gender.county')
			->leftJoin('gender', 'gender.ID', '=', 'vl_county_gender.gender')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->groupBy('gender.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_county_gender')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_gender.county')
			->leftJoin('gender', 'gender.ID', '=', 'vl_county_gender.gender')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
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

			$d = DB::table('vl_county_gender')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_gender.county')
			->leftJoin('gender', 'gender.ID', '=', 'vl_county_gender.gender')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
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
				$d = DB::table('vl_county_gender')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_gender.county')
				->leftJoin('gender', 'gender.ID', '=', 'vl_county_gender.gender')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->groupBy('gender.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_county_gender')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_gender.county')
				->leftJoin('gender', 'gender.ID', '=', 'vl_county_gender.gender')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode')
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

	public function age($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->county_string . $this->age_query();

		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_county_age')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_age.county')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_county_age.age')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->groupBy('agecategory.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_county_age')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_age.county')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_county_age.age')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->groupBy('agecategory.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_county_age')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_age.county')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_county_age.age')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
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

			$d = DB::table('vl_county_age')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'vl_county_age.county')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_county_age.age')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
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
				$d = DB::table('vl_county_age')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_age.county')
				->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_county_age.age')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->groupBy('agecategory.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_county_age')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'vl_county_age.county')
				->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_county_age.age')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode')
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

	

	public function county_sites($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->site_string . $this->summary_query();

		
		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
			->join('districts', 'districts.ID', '=', 'facilitys.district')
			->join('countys', 'countys.ID', '=', 'districts.county')
			->where('year', $year)
			->where($key, $county)
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
			->join('countys', 'countys.ID', '=', 'districts.county')
			->where('year', $year)
			->where($key, $county)
			->orderBy('vl_site_summary.month')
			->orderBy('facilitys.ID')
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
			->join('countys', 'countys.ID', '=', 'districts.county')
			->where('year', $year)
			->where($key, $county)
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
			->join('countys', 'countys.ID', '=', 'districts.county')
			->where('year', $year)
			->where($key, $county)
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
				->join('countys', 'countys.ID', '=', 'districts.county')
				->where('year', $year)
				->where($key, $county)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
				->orderBy('alltests')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_site_summary')
				->select( DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
				->join('districts', 'districts.ID', '=', 'facilitys.district')
				->join('countys', 'countys.ID', '=', 'districts.county')
				->whereRaw($q)
				->where($key, $county)
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
