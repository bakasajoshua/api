<?php

namespace App\Api\Vl\V1\Controllers;

use App\Partner;
use App\Api\Vl\V1\Controllers\BaseController;


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

		$raw = $this->partner_string . $this->summary_query();

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_partner_summary')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_partner_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_summary.partner')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_partner_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_summary.partner')
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

			$d = DB::table('vl_partner_summary')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_summary.partner')
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
				$d = DB::table('vl_partner_summary')
				->select('year', DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_summary.partner')
				->where('year', $year)
				->where('tat4', '!=', 0)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_partner_summary')
				->select( DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_summary.partner')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->where('tat4', '!=', 0)
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

	public function justification($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;


		$raw = $this->partner_string . $this->justification_query();
		

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_partner_justification')
			->select('year', DB::raw($raw))
			->leftJoin('viraljustifications', 'viraljustifications.ID', '=', 'vl_partner_justification.justification')
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_justification.partner')
			->when($partner, $this->get_when_callback($partner))
			->where('year', $year)
			->groupBy('partners.ID', 'partners.name', 'year')
			->groupBy('viraljustifications.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_partner_justification')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('viraljustifications', 'viraljustifications.ID', '=', 'vl_partner_justification.justification')
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_justification.partner')
			->when($partner, $this->get_when_callback($partner))
			->where('year', $year)
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->groupBy('viraljustifications.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_partner_justification')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('viraljustifications', 'viraljustifications.ID', '=', 'vl_partner_justification.justification')
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_justification.partner')
			->when($partner, $this->get_when_callback($partner))
			->where('year', $year)
			->where('month', $month)
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->groupBy('viraljustifications.name')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_partner_justification')
			->select('year', DB::raw($raw))
			->leftJoin('viraljustifications', 'viraljustifications.ID', '=', 'vl_partner_justification.justification')
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_justification.partner')
			->when($partner, $this->get_when_callback($partner))
			->where('year', $year)
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('viraljustifications.name')
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
				$d = DB::table('vl_partner_justification')
				->select('year', DB::raw($raw))
				->leftJoin('viraljustifications', 'viraljustifications.ID', '=', 'vl_partner_justification.justification')
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_justification.partner')
				->when($partner, $this->get_when_callback($partner))
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('viraljustifications.name')
				->groupBy('partners.ID', 'partners.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_partner_justification')
				->select( DB::raw($raw))
				->leftJoin('viraljustifications', 'viraljustifications.ID', '=', 'vl_partner_justification.justification')
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_justification.partner')
				->when($partner, $this->get_when_callback($partner))
				->whereRaw($q)
				->groupBy('viraljustifications.name')
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

	public function gender($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->partner_string . $this->gender_query();

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_partner_gender')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_gender.partner')
			->leftJoin('gender', 'gender.ID', '=', 'vl_partner_gender.gender')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('partners.ID', 'partners.name', 'year')
			->groupBy('gender.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_partner_gender')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_gender.partner')
			->leftJoin('gender', 'gender.ID', '=', 'vl_partner_gender.gender')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->groupBy('gender.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_partner_gender')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_gender.partner')
			->leftJoin('gender', 'gender.ID', '=', 'vl_partner_gender.gender')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
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

			$d = DB::table('vl_partner_gender')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_gender.partner')
			->leftJoin('gender', 'gender.ID', '=', 'vl_partner_gender.gender')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('vl_partner_gender')
				->select('year', DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_gender.partner')
				->leftJoin('gender', 'gender.ID', '=', 'vl_partner_gender.gender')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('partners.ID', 'partners.name', 'year')
				->groupBy('gender.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_partner_gender')
				->select( DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_gender.partner')
				->leftJoin('gender', 'gender.ID', '=', 'vl_partner_gender.gender')
				->when($partner, $this->get_when_callback($partner))
				->whereRaw($q)
				->groupBy('partners.ID', 'partners.name')
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

	public function age($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->partner_string . $this->age_query();

		 

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_partner_age')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_age.partner')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_partner_age.age')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('partners.ID', 'partners.name', 'year')
			->groupBy('agecategory.name')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_partner_age')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_age.partner')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_partner_age.age')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
			->groupBy('agecategory.name')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_partner_age')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_age.partner')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_partner_age.age')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', $month)
			->groupBy('month')
			->groupBy('partners.ID', 'partners.name', 'year')
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

			$d = DB::table('vl_partner_age')
			->select('year', DB::raw($raw))
			->leftJoin('partners', 'partners.ID', '=', 'vl_partner_age.partner')
			->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_partner_age.age')
			->where('year', $year)
			->when($partner, $this->get_when_callback($partner))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('partners.ID', 'partners.name', 'year')
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
				$d = DB::table('vl_partner_age')
				->select('year', DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_age.partner')
				->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_partner_age.age')
				->where('year', $year)
				->when($partner, $this->get_when_callback($partner))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('partners.ID', 'partners.name', 'year')
				->groupBy('agecategory.name')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_partner_age')
				->select( DB::raw($raw))
				->leftJoin('partners', 'partners.ID', '=', 'vl_partner_age.partner')
				->leftJoin('agecategory', 'agecategory.ID', '=', 'vl_partner_age.age')
				->whereRaw($q)
				->when($partner, $this->get_when_callback($partner))
				->groupBy('partners.ID', 'partners.name')
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

	public function partner_sites($partner, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->site_string . $this->summary_query();

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_site_summary')
			->select('year', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
			->join('partners', 'partners.ID', '=', 'facilitys.partner')
			->where('year', $year)
			->where('partners.ID', $partner)
			->orderBy('alltests')
			->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_site_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
			->join('partners', 'partners.ID', '=', 'facilitys.partner')
			->where('year', $year)
			->where('partners.ID', $partner)
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
			->join('partners', 'partners.ID', '=', 'facilitys.partner')
			->where('year', $year)
			->where('partners.ID', $partner)
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
			->join('partners', 'partners.ID', '=', 'facilitys.partner')
			->where('year', $year)
			->where('partners.ID', $partner)
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
				->join('partners', 'partners.ID', '=', 'facilitys.partner')
				->where('year', $year)
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->where('partners.ID', $partner)
				->groupBy('facilitys.ID', 'facilitys.name', 'facilitys.facilitycode', 'facilitys.DHIScode', 'year')
				->orderBy('alltests')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_site_summary')
				->select( DB::raw($raw))
				->leftJoin('facilitys', 'facilitys.ID', '=', 'vl_site_summary.facility')
				->join('partners', 'partners.ID', '=', 'facilitys.partner')
				->whereRaw($q)
				->where('partners.ID', $partner)
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
