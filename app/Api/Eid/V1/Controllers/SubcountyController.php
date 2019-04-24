<?php

namespace App\Api\Eid\V1\Controllers;

use Illuminate\Http\Request;
use App\District;
use App\Api\Eid\V1\Controllers\BaseController;

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

			$data = DB::table('subcounty_summary')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('subcounty_summary')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
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
				$d = DB::table('subcounty_summary')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('subcounty_summary')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
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

	public function hei_outcomes($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->subcounty_string . $this->hei_outcomes_query();
		
		$key = $this->set_key($subcounty);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('subcounty_summary')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('year', $year)
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('subcounty_summary')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
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
				$d = DB::table('subcounty_summary')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('subcounty_summary')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
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

	public function hei_validation($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->subcounty_string . $this->hei_validation_query();
		
		$key = $this->set_key($subcounty);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('subcounty_summary')
			->select('year', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('year', $year)
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('subcounty_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('subcounty_summary')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
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
				$d = DB::table('subcounty_summary')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('subcounty_summary')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_summary.subcounty')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
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


	public function age_breakdown($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->subcounty_string . $this->age_breakdown_query();
		
		$key = $this->set_key($subcounty);

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('subcounty_age_breakdown')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_age_breakdown.subcounty')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'subcounty_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('age_bands.name')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('subcounty_age_breakdown')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_age_breakdown.subcounty')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'subcounty_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('age_bands.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('subcounty_age_breakdown')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_age_breakdown.subcounty')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'subcounty_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('age_bands.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('subcounty_age_breakdown')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_age_breakdown.subcounty')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'subcounty_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('age_bands.name')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
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
				$d = DB::table('subcounty_age_breakdown')
				->select('year', DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_age_breakdown.subcounty')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'subcounty_age_breakdown.age_band_id')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('age_bands.name')
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('subcounty_age_breakdown')
				->select( DB::raw($raw))
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_age_breakdown.subcounty')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'subcounty_age_breakdown.age_band_id')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('age_bands.name')
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
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


	public function entry_point($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->subcounty_string . $this->entry_point_query();

		$key = $this->set_key($subcounty);

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('subcounty_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'subcounty_entrypoint.entrypoint')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_entrypoint.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('entry_points.name')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('subcounty_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'subcounty_entrypoint.entrypoint')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_entrypoint.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('entry_points.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('subcounty_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'subcounty_entrypoint.entrypoint')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_entrypoint.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('entry_points.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('subcounty_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'subcounty_entrypoint.entrypoint')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_entrypoint.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('entry_points.name')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
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
				$d = DB::table('subcounty_entrypoint')
				->select('year', DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'subcounty_entrypoint.entrypoint')
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_entrypoint.subcounty')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('entry_points.name')
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('subcounty_entrypoint')
				->select( DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'subcounty_entrypoint.entrypoint')
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_entrypoint.subcounty')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
				->groupBy('entry_points.name')
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

	public function mother_prophylaxis($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->subcounty_string . $this->mother_prophylaxis_query();

		$key = $this->set_key($subcounty);

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('subcounty_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_mprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_mprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('prophylaxis.name')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('subcounty_mprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_mprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_mprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('subcounty_mprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_mprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_mprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('subcounty_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_mprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_mprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('prophylaxis.name')
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
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
				$d = DB::table('subcounty_mprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_mprophylaxis.prophylaxis')
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_mprophylaxis.subcounty')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('prophylaxis.name')
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('subcounty_mprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_mprophylaxis.prophylaxis')
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_mprophylaxis.subcounty')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('prophylaxis.name')
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
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


	public function infant_prophylaxis($subcounty, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->subcounty_string . $this->infant_prophylaxis_query();

		$key = $this->set_key($subcounty);

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('subcounty_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_iprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_iprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('prophylaxis.name')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('subcounty_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_iprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_iprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('subcounty_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_iprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_iprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->where('month', $month)
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('subcounty_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_iprophylaxis.prophylaxis')
			->leftJoin('districts', 'districts.ID', '=', 'subcounty_iprophylaxis.subcounty')
			->where('year', $year)
			->when($subcounty, $this->get_when_callback($subcounty, $key))
			->groupBy('prophylaxis.name')
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
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
				$d = DB::table('subcounty_iprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_iprophylaxis.prophylaxis')
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_iprophylaxis.subcounty')
				->where('year', $year)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->whereBetween('month', [$month, $month2])
				->groupBy('prophylaxis.name')
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('subcounty_iprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'subcounty_iprophylaxis.prophylaxis')
				->leftJoin('districts', 'districts.ID', '=', 'subcounty_iprophylaxis.subcounty')
				->whereRaw($q)
				->when($subcounty, $this->get_when_callback($subcounty, $key))
				->groupBy('prophylaxis.name')
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
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

			$data = DB::table('site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
			->join('districts', 'districts.ID', '=', 'facilitys.district')
			->where('year', $year)
			->where($key, $subcounty)
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
			->where('year', $year)
			->where($key, $subcounty)
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

			$d = DB::table('site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
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
				$d = DB::table('site_summary')
				->select('year', DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
				->join('districts', 'districts.ID', '=', 'facilitys.district')
				->where('year', $year)
				->where($key, $subcounty)
				->whereBetween('month', [$month, $month2])
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('site_summary')
				->select( DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
				->join('districts', 'districts.ID', '=', 'facilitys.district')
				->whereRaw($q)
				->where($key, $subcounty)
				->groupBy('districts.ID', 'districts.name', 'districts.SubCountyMFLCode', 'districts.SubCountyDHISCode')
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
