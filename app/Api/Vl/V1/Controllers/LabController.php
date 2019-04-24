<?php

namespace App\Api\Vl\V1\Controllers;

use App\Lab;
use App\Api\Vl\V1\Controllers\BaseController;

use DB;

class LabController extends BaseController
{
    //

	private function get_when_callback($lab)
	{
		return function($query) use ($lab){
				if($lab != 0) return $query->where('labs.ID', $lab);
			};
	}

    public function labs(){
    	return DB::table('labs')->orderBy('ID')->get();
    }

    public function info($lab){
    	return DB::table('labs')->where('ID', $lab)->get();
    }

	public function summary($lab, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){

		$data = NULL;

		$raw = $this->lab_string . $this->summary_query();

		// Totals for the whole year
		if($type == 1){

			$data = DB::table('vl_lab_summary')
			->select('year', DB::raw($raw))
			->leftJoin('labs', 'labs.ID', '=', 'vl_lab_summary.lab')
			->where('year', $year)
			->when($lab, $this->get_when_callback($lab))
			->groupBy('labs.ID', 'labs.name', 'year')
			->get();

		}

		// For the whole year but has per month
		else if($type == 2){
			$data = DB::table('vl_lab_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('labs', 'labs.ID', '=', 'vl_lab_summary.lab')
			->where('year', $year)
			->when($lab, $this->get_when_callback($lab))
			->groupBy('month')
			->groupBy('labs.ID', 'labs.name', 'year')
			->get();
		}

		// For a particular month
		else if($type == 3){

			if($month < 1 || $month > 12) return $this->invalid_month($month);
			$data = DB::table('vl_lab_summary')
			->select('year', 'month', DB::raw($raw))
			->leftJoin('labs', 'labs.ID', '=', 'vl_lab_summary.lab')
			->where('year', $year)
			->when($lab, $this->get_when_callback($lab))
			->where('month', $month)
			->groupBy('month')
			->groupBy('labs.ID', 'labs.name', 'year')
			->get();
		}

		// For a particular quarter
		// The month value will be used as the quarter value
		else if($type == 4){

			if($month < 1 || $month > 4) return $this->invalid_quarter($month);
			
			$my_range = $this->quarter_range($month);
			$lesser = $my_range[0];
			$greater = $my_range[1];

			$d = DB::table('vl_lab_summary')
			->select('year', DB::raw($raw))
			->leftJoin('labs', 'labs.ID', '=', 'vl_lab_summary.lab')
			->where('year', $year)
			->when($lab, $this->get_when_callback($lab))
			->where('month', '>', $lesser)
			->where('month', '<', $greater)
			->groupBy('labs.ID', 'labs.name', 'year')
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
				$d = DB::table('vl_lab_summary')
				->select('year', DB::raw($raw))
				->leftJoin('labs', 'labs.ID', '=', 'vl_lab_summary.lab')
				->where('year', $year)
				->where('tat4', '!=', 0)
				->when($lab, $this->get_when_callback($lab))
				->whereBetween('month', [$month, $month2])
				->groupBy('year')
				->groupBy('labs.ID', 'labs.name', 'year')
				->get();
			}

			if($year < $year2){
				$d = DB::table('vl_lab_summary')
				->select( DB::raw($raw))
				->leftJoin('labs', 'labs.ID', '=', 'vl_lab_summary.lab')
				->whereRaw($q)
				->when($lab, $this->get_when_callback($lab))
				->where('tat4', '!=', 0)
				->groupBy('labs.ID', 'labs.name')
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
