<?php

namespace App\Api\Eid\V1\Controllers;

use Illuminate\Http\Request;
use App\National;
use App\Api\Eid\V1\Controllers\BaseController;

use DB;

class NationalController extends BaseController
{
    //

	public function summary($type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;


		$raw = $this->summary_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('national_summary')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->groupBy('year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('national_summary')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('national_summary')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->where('month', $month)
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('national_summary')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('year')
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
				$d = DB::table('national_summary')
				->select('year', DB::raw($raw))
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('national_summary')
				->select( DB::raw($raw))
				->whereRaw($q)
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

	public function hei_outcomes($type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->hei_outcomes_query();		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('national_summary')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->groupBy('year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('national_summary')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('national_summary')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->where('month', $month)
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('national_summary')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('year')
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
				$d = DB::table('national_summary')
				->select('year', DB::raw($raw))
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('national_summary')
				->select( DB::raw($raw))
				->whereRaw($q)
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

	public function hei_validation($type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->hei_validation_query();		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('national_summary')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->groupBy('year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('national_summary')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('national_summary')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->where('month', $month)
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('national_summary')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('year')
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
				$d = DB::table('national_summary')
				->select('year', DB::raw($raw))
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('national_summary')
				->select( DB::raw($raw))
				->whereRaw($q)
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


	public function age_breakdown($type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->age_breakdown_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('national_age_breakdown')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'national_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->groupBy('year', 'name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('national_age_breakdown')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'national_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->groupBy('month')
			->groupBy('year', 'name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('national_age_breakdown')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'national_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->where('month', $month)
			->groupBy('month')
			->groupBy('year', 'name')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('national_age_breakdown')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'national_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('year', 'name')
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
				$d = DB::table('national_age_breakdown')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'national_age_breakdown.age_band_id')
				->select('year', DB::raw($raw))
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year', 'name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('national_age_breakdown')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'national_age_breakdown.age_band_id')
				->select( DB::raw($raw))
				->whereRaw($q)
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


	public function entry_point($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->entry_point_query();

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('national_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'national_entrypoint.entrypoint')
			->where('year', $year)
			->groupBy('name')
			->groupBy('year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('national_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'national_entrypoint.entrypoint')
			->where('year', $year)
			->groupBy('name')
			->groupBy('month')
			->groupBy('year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('national_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'national_entrypoint.entrypoint')
			->where('year', $year)
			->where('month', $month)
			->groupBy('name')
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('national_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'national_entrypoint.entrypoint')
			->where('year', $year)
			->groupBy('name')
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('year')
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
				$d = DB::table('national_entrypoint')
				->select('year', DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'national_entrypoint.entrypoint')
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('national_entrypoint')
				->select( DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'national_entrypoint.entrypoint')
				->whereRaw($q)
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

	public function mother_prophylaxis($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->mother_prophylaxis_query();

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('national_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
			->where('year', $year)
			->groupBy('name')
			->groupBy('year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('national_mprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
			->where('year', $year)
			->groupBy('name')
			->groupBy('month')
			->groupBy('year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('national_mprophylaxis')
			->select('year', 'month', DB::raw($raw))->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
			->where('year', $year)
			->where('month', $month)
			->groupBy('name')
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('national_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
			->where('year', $year)
			->groupBy('name')
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('year')
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
				$d = DB::table('national_mprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('national_mprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
				->whereRaw($q)
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


	public function infant_prophylaxis($type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->infant_prophylaxis_query();

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('national_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_iprophylaxis.prophylaxis')
			->where('year', $year)
			->groupBy('name')
			->groupBy('year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('national_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_iprophylaxis.prophylaxis')
			->where('year', $year)
			->groupBy('name')
			->groupBy('month')
			->groupBy('year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('national_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_iprophylaxis.prophylaxis')
			->where('year', $year)
			->where('month', $month)
			->groupBy('name')
			->groupBy('month')
			->groupBy('year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('national_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_iprophylaxis.prophylaxis')
			->where('year', $year)
			->groupBy('name')
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('year')
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
				$d = DB::table('national_iprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('national_iprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'national_mprophylaxis.prophylaxis')
				->whereRaw($q)
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
