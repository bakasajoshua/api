<?php

namespace App\Api\Eid\V1\Controllers;

use Illuminate\Http\Request;
use App\County;
use App\Api\Eid\V1\Controllers\BaseController;

use DB;

class CountyController extends BaseController
{
    //

   //  public $when_clause = function($query) use ($county, $key){
			// 	if($county != "0" || $county != 0){
			// 		return $query->where($key, $county);
			// 	}
					
			// };

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

    	// return $county;

		$data = NULL;

		$raw = $this->county_string . $this->summary_query();

		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('county_summary')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
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

			$d = DB::table('county_summary')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
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
				$d = DB::table('county_summary')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('county_summary')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
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

	public function hei_outcomes($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->county_string . $this->hei_outcomes_query();
		
		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('county_summary')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->when($county, $this->get_when_callback($county, $key))
			->where('year', $year)
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
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

			$d = DB::table('county_summary')
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();
			
			$a = "Q" . $month;
			$b = $this->quarter_description($month);
			
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
				$d = DB::table('county_summary')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('county_summary')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
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

	public function hei_validation($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->county_string . $this->hei_validation_query();
		
		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('county_summary')
			->select('year', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->when($county, $this->get_when_callback($county, $key))
			->where('year', $year)
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('county_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
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

			$d = DB::table('county_summary')
			->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
			->select('year', DB::raw($raw))
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
			$data = array($temp);
		}

		// For Multiple Months across years
		else if($type == 5){

			if($year > $year2) return $this->pass_error('From year is greater');
			if($year == $year2 && $month >= $month2) return $this->pass_error('From month is greater');

			$q = $this->multiple_year($year, $month, $year2, $month2);
			// return $this->pass_error($q);

			if($year == $year2 && $month < $month2){
				$d = DB::table('county_summary')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('county_summary')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_summary.county')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
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


	public function age_breakdown($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->county_string . $this->age_breakdown_query();
		
		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('county_age_breakdown')
			->leftJoin('countys', 'countys.ID', '=', 'county_age_breakdown.county')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'county_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('age_bands.name')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('county_age_breakdown')
			->leftJoin('countys', 'countys.ID', '=', 'county_age_breakdown.county')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'county_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('age_bands.name')
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('county_age_breakdown')
			->leftJoin('countys', 'countys.ID', '=', 'county_age_breakdown.county')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'county_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('age_bands.name')
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

			$d = DB::table('county_age_breakdown')
			->leftJoin('countys', 'countys.ID', '=', 'county_age_breakdown.county')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'county_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('age_bands.name')
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
				$d = DB::table('county_age_breakdown')
				->select('year', DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_age_breakdown.county')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'county_age_breakdown.age_band_id')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('age_bands.name')
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('county_age_breakdown')
				->select( DB::raw($raw))
				->leftJoin('countys', 'countys.ID', '=', 'county_age_breakdown.county')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'county_age_breakdown.age_band_id')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
				->groupBy('age_bands.name')
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


	public function entry_point($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->county_string . $this->entry_point_query();

		$key = $this->set_key($county);

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('county_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'county_entrypoint.entrypoint')
			->leftJoin('countys', 'countys.ID', '=', 'county_entrypoint.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('entry_points.name')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('county_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'county_entrypoint.entrypoint')
			->leftJoin('countys', 'countys.ID', '=', 'county_entrypoint.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('entry_points.name')
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('county_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'county_entrypoint.entrypoint')
			->leftJoin('countys', 'countys.ID', '=', 'county_entrypoint.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('entry_points.name')
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


			$d = DB::table('county_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'county_entrypoint.entrypoint')
			->leftJoin('countys', 'countys.ID', '=', 'county_entrypoint.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('entry_points.name')
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
				$d = DB::table('county_entrypoint')
				->select('year', DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'county_entrypoint.entrypoint')
				->leftJoin('countys', 'countys.ID', '=', 'county_entrypoint.county')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('entry_points.name')
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('county_entrypoint')
				->select( DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'county_entrypoint.entrypoint')
				->leftJoin('countys', 'countys.ID', '=', 'county_entrypoint.county')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
				->groupBy('entry_points.name')
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

	public function mother_prophylaxis($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->county_string . $this->mother_prophylaxis_query();

		$key = $this->set_key($county);

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('county_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_mprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_mprophylaxis.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('prophylaxis.name')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('county_mprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_mprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_mprophylaxis.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('county_mprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_mprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_mprophylaxis.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('prophylaxis.name')
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


			$d = DB::table('county_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_mprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_mprophylaxis.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('prophylaxis.name')
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
				$d = DB::table('county_mprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_mprophylaxis.prophylaxis')
				->leftJoin('countys', 'countys.ID', '=', 'county_mprophylaxis.county')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('prophylaxis.name')
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('county_mprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_mprophylaxis.prophylaxis')
				->leftJoin('countys', 'countys.ID', '=', 'county_mprophylaxis.county')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
				->groupBy('prophylaxis.name')
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


	public function infant_prophylaxis($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->county_string . $this->infant_prophylaxis_query();

		$key = $this->set_key($county);

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('county_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_iprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_iprophylaxis.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('prophylaxis.name')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('county_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_iprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_iprophylaxis.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('county_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_iprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_mprophylaxis.county')
			->where('year', $year)
			->when($county, $this->get_when_callback($county, $key))
			->where('month', $month)
			->groupBy('prophylaxis.name')
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


			$d = DB::table('county_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_iprophylaxis.prophylaxis')
			->leftJoin('countys', 'countys.ID', '=', 'county_iprophylaxis.county')
			->where('year', $year)
			->when($county, function($query) use ($county, $key){
				if($county != "0" || $county != 0){
					return $query->where($key, $county);
				}
			})
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('prophylaxis.name')
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
				$d = DB::table('county_iprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_iprophylaxis.prophylaxis')
				->leftJoin('countys', 'countys.ID', '=', 'county_iprophylaxis.county')
				->where('year', $year)
				->when($county, $this->get_when_callback($county, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('prophylaxis.name')
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('county_iprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'county_iprophylaxis.prophylaxis')
				->leftJoin('countys', 'countys.ID', '=', 'county_iprophylaxis.county')
				->whereRaw($q)
				->when($county, $this->get_when_callback($county, $key))
				->groupBy('prophylaxis.name')
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

	public function county_sites($county, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->site_string . $this->summary_query();

		
		$key = $this->set_key($county);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
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
			$data = DB::table('site_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
			->join('districts', 'districts.ID', '=', 'facilitys.district')
			->join('countys', 'countys.ID', '=', 'districts.county')
			->where('year', $year)
			->where($key, $county)
			->orderBy('facilitys.ID')
			->orderBy('site_summary.month')
			->groupBy('month')
			->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('site_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
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

			$d = DB::table('site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
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
				$d = DB::table('site_summary')
				->select('year', DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
				->join('districts', 'districts.ID', '=', 'facilitys.district')
				->join('countys', 'countys.ID', '=', 'districts.county')
				->where('year', $year)
				->where($key, $county)
				->whereBetween('month', [$month, $month2])
				->groupBy('countys.ID', 'countys.name', 'countys.CountyDHISCode', 'countys.CountyMFLCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('site_summary')
				->select( DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
				->join('districts', 'districts.ID', '=', 'facilitys.district')
				->join('countys', 'countys.ID', '=', 'districts.county')
				->whereRaw($q)
				->where($key, $county)
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
}
