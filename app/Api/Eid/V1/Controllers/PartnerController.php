<?php

namespace App\Api\Eid\V1\Controllers;

use Illuminate\Http\Request;
use App\Partner;
use App\Api\Eid\V1\Controllers\BaseController;

use DB;

class PartnerController extends BaseController
{
    //

	private function get_when_callback($partner)
	{
		return function($query) use ($partner){
				if($partner != 0) return $query->where('partners.ID', $partner);
			};
	}

    public function partners(){
    	return DB::table('partners')->orderBy('ID')->get();
    }

    public function info($partner){
    	return DB::table('partners')->where('ID', $partner)->orWhere('partnerDHISCode', $partner)->get();
    }

    public function summary($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->partner_string . $this->partner_summary_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('ip_summary')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('ip_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('ip_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('ip_summary')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('ip_summary')
				->select('year', DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('ip_summary')
				->select( DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('partners.ID', 'partners.name')
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

	public function hei_outcomes($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->partner_string . $this->hei_outcomes_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('ip_summary')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->when($partner, $this->get_when_callback($partner))
			->where('year', $year)
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('ip_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('ip_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('ip_summary')
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('ip_summary')
				->select('year', DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('ip_summary')
				->select( DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('partners.ID', 'partners.name')
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

	public function hei_validation($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->partner_string . $this->hei_validation_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('ip_summary')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->when($partner, $this->get_when_callback($partner))
			->where('year', $year)
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('ip_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('ip_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('ip_summary')
			->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('ip_summary')
				->select('year', DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('ip_summary')
				->select( DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_summary.partner')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('partners.ID', 'partners.name')
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


	public function age_breakdown($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->partner_string . $this->age_breakdown_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('ip_age_breakdown')
			->leftJoin('partners', 'partners.ID', '=', 'ip_age_breakdown.partner')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'ip_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('age_bands.name')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('ip_age_breakdown')
			->leftJoin('partners', 'partners.ID', '=', 'ip_age_breakdown.partner')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'ip_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('age_bands.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('ip_age_breakdown')
			->leftJoin('partners', 'partners.ID', '=', 'ip_age_breakdown.partner')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'ip_age_breakdown.age_band_id')
			->select('year', 'month', DB::raw($raw))
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('age_bands.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('ip_age_breakdown')
			->leftJoin('partners', 'partners.ID', '=', 'ip_age_breakdown.partner')
			->leftJoin('age_bands', 'age_bands.ID', '=', 'ip_age_breakdown.age_band_id')
			->select('year', DB::raw($raw))
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('age_bands.name')
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('ip_age_breakdown')
				->select('year', DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_age_breakdown.partner')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'ip_age_breakdown.age_band_id')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('age_bands.name')
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('ip_age_breakdown')
				->select( DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'ip_age_breakdown.partner')
				->leftJoin('age_bands', 'age_bands.ID', '=', 'ip_age_breakdown.age_band_id')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('age_bands.name')
				->groupBy('partners.ID', 'partners.name')
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


	public function entry_point($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->partner_string . $this->entry_point_query();

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('ip_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'ip_entrypoint.entrypoint')
			->leftJoin('partners', 'partners.ID', '=', 'ip_entrypoint.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('entry_points.name')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('ip_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'ip_entrypoint.entrypoint')
			->leftJoin('partners', 'partners.ID', '=', 'ip_entrypoint.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('entry_points.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('ip_entrypoint')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'ip_entrypoint.entrypoint')
			->leftJoin('partners', 'partners.ID', '=', 'ip_entrypoint.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('entry_points.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('ip_entrypoint')
			->select('year', DB::raw($raw))
			->leftJoin('entry_points', 'entry_points.ID', '=', 'ip_entrypoint.entrypoint')
			->leftJoin('partners', 'partners.ID', '=', 'ip_entrypoint.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('entry_points.name')
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('ip_entrypoint')
				->select('year', DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'ip_entrypoint.entrypoint')
				->leftJoin('partners', 'partners.ID', '=', 'ip_entrypoint.partner')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('entry_points.name')
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('ip_entrypoint')
				->select( DB::raw($raw))
				->leftJoin('entry_points', 'entry_points.ID', '=', 'ip_entrypoint.entrypoint')
				->leftJoin('partners', 'partners.ID', '=', 'ip_entrypoint.partner')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('entry_points.name')
				->groupBy('partners.ID', 'partners.name')
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

	public function mother_prophylaxis($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->partner_string . $this->mother_prophylaxis_query();

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('ip_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_mprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_mprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('prophylaxis.name')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('ip_mprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_mprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_mprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('ip_mprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_mprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_mprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('ip_mprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_mprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_mprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('prophylaxis.name')
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('ip_mprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_mprophylaxis.prophylaxis')
				->leftJoin('partners', 'partners.ID', '=', 'ip_mprophylaxis.partner')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('prophylaxis.name')
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('ip_mprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_mprophylaxis.prophylaxis')
				->leftJoin('partners', 'partners.ID', '=', 'ip_mprophylaxis.partner')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('prophylaxis.name')
				->groupBy('partners.ID', 'partners.name')
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


	public function infant_prophylaxis($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
		$data = NULL;

		$raw = $this->partner_string . $this->infant_prophylaxis_query();

		  // Totals for the whole year
		if($type == 1){

			$data = DB::table('ip_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_iprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_iprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('prophylaxis.name')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){

			$data = DB::table('ip_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_iprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_iprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);

			$data = DB::table('ip_iprophylaxis')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_iprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_iprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('prophylaxis.name')
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);

			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];


			$d = DB::table('ip_iprophylaxis')
			->select('year', DB::raw($raw))
			->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_iprophylaxis.prophylaxis')
			->leftJoin('partners', 'partners.ID', '=', 'ip_iprophylaxis.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('prophylaxis.name')->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('ip_iprophylaxis')
				->select('year', DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_iprophylaxis.prophylaxis')
				->leftJoin('partners', 'partners.ID', '=', 'ip_iprophylaxis.partner')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('prophylaxis.name')
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('ip_iprophylaxis')
				->select( DB::raw($raw))
				->leftJoin('prophylaxis', 'prophylaxis.ID', '=', 'ip_iprophylaxis.prophylaxis')
				->leftJoin('partners', 'partners.ID', '=', 'ip_iprophylaxis.partner')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('prophylaxis.name')
				->groupBy('partners.ID', 'partners.name')
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

	public function partner_sites($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->site_string . $this->summary_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
			->where('year', $year)
			->where('facilitys.partner', $partner)
			->orderBy('alltests')
			->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('site_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
			->where('year', $year)
			->where('facilitys.partner', $partner)
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
			->where('year', $year)
			->where('facilitys.partner', $partner)
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
			->where('year', $year)
			->where('facilitys.partner', $partner)
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
				->where('year', $year)
				->where('facilitys.partner', $partner)
				->whereBetween('month', [$month, $month2])
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('site_summary')
				->select( DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'site_summary.facility')
				->whereRaw($q)
				->where('facilitys.partner', $partner)
				->groupBy('partners.ID', 'partners.name')
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
