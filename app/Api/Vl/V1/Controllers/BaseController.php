<?php

namespace App\Api\Vl\V1\Controllers;



use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Carbon\Carbon;


class BaseController extends Controller
{
	use Helpers;

	protected $county_string = 'countys.ID as county_id, CountyDHISCode as CountyDHISCode, CountyMFLCode as CountyMFLCode, CountyDHISCode as CountyDHISCode, CountyMFLCode as CountyMFLCode, countys.name as county, ';

	protected $subcounty_string = 'districts.ID as subcounty_id, districts.SubCountyDHISCode as SubCountyDHISCode, districts.SubCountyMFLCode as SubCountyMFLCode, districts.name as subcounty, ';

	protected $lab_string = 'labs.ID as lab_id, labs.name as lab, ';

	protected $partner_string = 'partners.ID as partner_id, partners.name as partner, ';

	protected $site_string = 'facilitys.ID as facility_id, facilitys.facilitycode as FacilityMFLCode, facilitys.DHIScode as FacilityDHISCode, facilitys.name as facility, ';

	protected $patient_string = 'count(*) as `viralloads`, FacilityMFLcode, patientID';

	protected $report_string = "FacilityMFLcode, patientID, datetested, result";


	protected function pass_error($message){
    	// return response()
     //        ->json([
     //            'error' => 500,
     //            'message' =>  $message
     //        ]);
        return $this->response->errorBadRequest($message);
    }

    protected function invalid_month($month){
    	$message = 'Month ' . $month . ' is invalid. Value must be between 1 and 12.';
        return $this->pass_error($month);
	}

	protected function invalid_quarter($quarter){
		$message = 'Quarter ' . $quarter . ' is invalid. Value must be between 1 and 4.';
        return $this->pass_error($message);
	}

	protected function invalid_type($type){
        $message = 'Type ' . $type . ' is invalid. Value must be between 1 and 5. 1 is for the total for the whole year. 2 is for the year with data grouped by month. 3 is for the a particular month. 4 for a particular quarter. 5 for a date range.';
        return $this->pass_error($message);
	}

	protected function quarter_range($month){
		$greater;
		$lesser;

		switch ($month) {
			case 1:
				$greater = 4;
				$lesser = 0;
				break;
			case 2:
				$greater = 7;
				$lesser = 3;
				break;
			case 3:
				$greater = 10;
				$lesser = 6;
				break;
			case 4:
				$greater = 13;
				$lesser = 9;
				break;
			
			default:
				// return $this->invalid_quarter($month);
				break;
		}

		return array($lesser, $greater);
	}

	protected function quarter_description($month){
		$str;
		switch ($month) {
			case 1:
				$str = "From January to March.";
				break;
			case 2:
				$str = "From April to June.";
				break;
			case 3:
				$str = "From July to September.";
				break;
			case 4:
				$str = "From October to December.";
				break;
			
			default:
				// return $this->invalid_quarter($month);
				break;
		}

		return $str;
	}

	// For national summary query
	protected function nat_summary_query(){
		return '
			SUM(`actualpatients`) as `actualpatients`,
			SUM(`received`) as `received`,
			SUM(`alltests`) as `alltests`,
			SUM(`sustxfail`) as `sustxfail`,
			SUM(`confirmtx`) as `confirmtx`,
			SUM(`confirm2vl`) as `confirm2vl`,
			SUM(`repeattests`) as `repeattests`,
			SUM(`rejected`) as `rejected`,
			SUM(`dbs`) as `dbs`,
			SUM(`plasma`) as `plasma`,
			SUM(`edta`) as `edta`,
			SUM(`maletest`) as `maletest`,
			SUM(`femaletest`) as `femaletest`,
			SUM(`nogendertest`) as `nogendertest`,
			SUM(`adults`) as `adults`,
			SUM(`paeds`) as `paeds`,
			SUM(`noage`) as `noage`,
			SUM(`Undetected` + `less1000`) as `suppressed`,
			SUM(`less5000` + `above5000`) as `non suppressed`,
			SUM(`invalids`) as `invalids`,
			AVG(`sitessending`) as `sitessending`,
			AVG(`tat1`) as `collection to lab receipt`,
			AVG(`tat2`) as `lab receipt to testing`,
			AVG(`tat3`) as `tested to dispatch`,
			AVG(`tat4`) as `collection to dispatch`,
			SUM(`less2`) as `less 2 years`,
			SUM(`less9`) as `less 9 years`,
			SUM(`less14`) as `less 14 years`,
			SUM(`less24`) as `less 24 years`,
			SUM(`over25`) as `over 25 years`
		';
	}

	// For sample type
	protected function sample_type_query(){
		return '
			`viralsampletype`.`name` as `SampleType`,
			SUM(`tests`) as `tests`,
			SUM(`sustxfail`) as `sustxfail`,
			SUM(`confirmtx`) as `confirmtx`,
			SUM(`confirm2vl`) as `confirm2vl`,
			SUM(`repeattests`) as `repeattests`,
			SUM(`rejected`) as `rejected`,
			SUM(`maletest`) as `maletest`,
			SUM(`femaletest`) as `femaletest`,
			SUM(`adults`) as `adults`,
			SUM(`paeds`) as `paeds`,
			SUM(`noage`) as `noage`,
			SUM(`Undetected` + `less1000`) as `suppressed`,
			SUM(`less5000` + `above5000`) as `non suppressed`,
			SUM(`invalids`) as `invalids`,
			SUM(`less2`) as `less 2 years`,
			SUM(`less9`) as `less 9 years`,
			SUM(`less14`) as `less 14 years`,
			SUM(`less24`) as `less 24 years`,
			SUM(`over25`) as `over 25 years`
		';
	}

	// For regimen
	// Removed confirm2vl column
	// Column is only present in national level
	protected function regimen_query(){
		return '
			`viralprophylaxis`.`name` as `regimen`,
			SUM(`tests`) as `tests`,
			SUM(`sustxfail`) as `sustxfail`,
			SUM(`confirmtx`) as `confirmtx`,
			SUM(`repeattests`) as `repeattests`,
			SUM(`rejected`) as `rejected`,
			SUM(`maletest`) as `maletest`,
			SUM(`femaletest`) as `femaletest`,
			SUM(`adults`) as `adults`,
			SUM(`paeds`) as `paeds`,
			SUM(`noage`) as `noage`,
			SUM(`Undetected` + `less1000`) as `suppressed`,
			SUM(`less5000` + `above5000`) as `non suppressed`,
			SUM(`invalids`) as `invalids`,
			SUM(`less2`) as `less 2 years`,
			SUM(`less9`) as `less 9 years`,
			SUM(`less14`) as `less 14 years`,
			SUM(`less24`) as `less 24 years`,
			SUM(`over25`) as `over 25 years`
		';
	}

	// For justification
	protected function justification_query(){
		return '
			`viraljustifications`.`name` as `justification`,
			SUM(`sustxfail`) as `sustxfail`,
			SUM(`confirmtx`) as `confirmtx`,
			SUM(`repeattests`) as `repeattests`,
			SUM(`confirm2vl`) as `confirm2vl`,
			SUM(`rejected`) as `rejected`,
			SUM(`dbs`) as `dbs`,
			SUM(`plasma`) as `plasma`,
			SUM(`edta`) as `edta`,
			SUM(`maletest`) as `maletest`,
			SUM(`femaletest`) as `femaletest`,
			SUM(`adults`) as `adults`,
			SUM(`paeds`) as `paeds`,
			SUM(`noage`) as `noage`,
			SUM(`Undetected` + `less1000`) as `suppressed`,
			SUM(`less5000` + `above5000`) as `non suppressed`,
			SUM(`invalids`) as `invalids`,
			SUM(`less2`) as `less 2 years`,
			SUM(`less9`) as `less 9 years`,
			SUM(`less14`) as `less 14 years`,
			SUM(`less24`) as `less 24 years`,
			SUM(`over25`) as `over 25 years`
		';
	}

	// For gender
	// Removed confirm2vl column
	// Column is only present in national level
	protected function gender_query(){
		return '
			`gender`.`name` as `gender`,
			SUM(`tests`) as `tests`,
			SUM(`sustxfail`) as `sustxfail`,
			SUM(`confirmtx`) as `confirmtx`,
			SUM(`repeattests`) as `repeattests`,
			SUM(`rejected`) as `rejected`,
			SUM(`dbs`) as `dbs`,
			SUM(`plasma`) as `plasma`,
			SUM(`edta`) as `edta`,
			SUM(`adults`) as `adults`,
			SUM(`paeds`) as `paeds`,
			SUM(`noage`) as `noage`,
			SUM(`Undetected` + `less1000`) as `suppressed`,
			SUM(`less5000` + `above5000`) as `non suppressed`,
			SUM(`invalids`) as `invalids`,
			SUM(`less2`) as `less 2 years`,
			SUM(`less9`) as `less 9 years`,
			SUM(`less14`) as `less 14 years`,
			SUM(`less24`) as `less 24 years`,
			SUM(`over25`) as `over 25 years`
		';
	}

	// For age
	// Removed confirm2vl column
	// Column is only present in national level
	protected function age_query(){
		return '
			`agecategory`.`name` as `AgeCategory`,
			SUM(`tests`) as `tests`,
			SUM(`sustxfail`) as `sustxfail`,
			SUM(`confirmtx`) as `confirmtx`,
			SUM(`repeattests`) as `repeattests`,
			SUM(`rejected`) as `rejected`,
			SUM(`dbs`) as `dbs`,
			SUM(`plasma`) as `plasma`,
			SUM(`edta`) as `edta`,
			SUM(`maletest`) as `maletest`,
			SUM(`femaletest`) as `femaletest`,
			SUM(`nogendertest`) as `nogendertest`,
			SUM(`Undetected` + `less1000`) as `suppressed`,
			SUM(`less5000` + `above5000`) as `non suppressed`,
			SUM(`invalids`) as `invalids`
		';
	}

	// For all other summary queries
	protected function summary_query(){
		return '
			SUM(`alltests`) as `alltests`,
			SUM(`received`) as `received`,
			SUM(`alltests`) as `alltests`,
			SUM(`sustxfail`) as `sustxfail`,
			SUM(`confirmtx`) as `confirmtx`,
			SUM(`repeattests`) as `repeattests`,
			SUM(`confirm2vl`) as `confirm2vl`,
			SUM(`rejected`) as `rejected`,
			SUM(`dbs`) as `dbs`,
			SUM(`plasma`) as `plasma`,
			SUM(`edta`) as `edta`,
			SUM(`maletest`) as `maletest`,
			SUM(`femaletest`) as `femaletest`,
			SUM(`nogendertest`) as `nogendertest`,
			SUM(`adults`) as `adults`,
			SUM(`paeds`) as `paeds`,
			SUM(`noage`) as `noage`,
			SUM(`Undetected` + `less1000`) as `suppressed`,
			SUM(`less5000` + `above5000`) as `non suppressed`,
			SUM(`invalids`) as `invalids`,
			AVG(`sitessending`) as `sitessending`,
			AVG(`tat1`) as `collection to lab receipt`,
			AVG(`tat2`) as `lab receipt to testing`,
			AVG(`tat3`) as `tested to dispatch`,
			AVG(`tat4`) as `collection to dispatch`,
			SUM(`less2`) as `less 2 years`,
			SUM(`less9`) as `less 9 years`,
			SUM(`less14`) as `less 14 years`,
			SUM(`less24`) as `less 24 years`,
			SUM(`over25`) as `over 25 years`
		';
	}


	

	protected function patient_query(){
		return 'PatientID, facilitys.name as Facility, districts.name as Subcounty, countys.name as County, partners.name as Partner,  Age, Gender, PatientPhoneNo, viralsampletypedetails.name as SampleType, viraljustifications.name as Justification, viralprophylaxis.name as Regimen, datecollected, datereceived, datetested, result, datedispatched, labtestedin, labs.name as Lab, viralrejectedreasons.Name as RejectedReason, receivedstatus.name as ReceivedStatus';
	}



	protected function output_data($data, $type, $year, $month=NULL){
		if($type == 1){
			//$data['year'] = $year;
			return $data;
		}
	}

	protected function multiple_year($year, $month=NULL, $year2=NULL, $month2=NULL){
		 return "( (year = " . $year . " AND month >= " . $month . ") OR (year = " . $year2 . " AND month <= " . $month . ") OR (year > " . $year . " AND year < " . $year2 . ")  )";
	}

	protected function describe_multiple($year, $month=NULL, $year2=NULL, $month2=NULL){
		return "For the period from " . $this->resolve_month($month) . ", {$year} to " . $this->resolve_month($month2) . ", {$year2}."; 

	}

	protected function check_data(&$data){
		if ($data == null) {
			return $this->pass_error("No data found");
		}else{
			if(gettype($data) == "object"){
				if($data->isEmpty()){
					return $this->pass_error("No data found");
				}
			}
			return $data;
		}
	}

	protected function resolve_month($month)
	{
		switch ($month) {
			case 1:
				$value = 'January';
				break;
			case 2:
				$value = 'February';
				break;
			case 3:
				$value = 'March';
				break;
			case 4:
				$value = 'April';
				break;
			case 5:
				$value = 'May';
				break;
			case 6:
				$value = 'June';
				break;
			case 7:
				$value = 'July';
				break;
			case 8:
				$value = 'August';
				break;
			case 9:
				$value = 'September';
				break;
			case 10:
				$value = 'October';
				break;
			case 11:
				$value = 'November';
				break;
			case 12:
				$value = 'December';
				break;
			default:
				$value = NULL;
				break;
		}

		return $value;

	}

	public function set_date($year, $month, $year2, $month2)
	{		
		$min = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-01")->toDateString();
		$max = Carbon::createFromFormat('Y-m-d', "{$year2}-{$month2}-01")->addMonth()->subDay()->toDateString();

		return [$min, $max];
	}

	public function set_quarters($year, $quarter){
		$greater;
		$lesser;

		switch ($quarter) {
			case 1:
				$greater = 3;
				$lesser = 1;
				break;
			case 2:
				$greater = 6;
				$lesser = 4;
				break;
			case 3:
				$greater = 9;
				$lesser = 7;
				break;
			case 4:
				$greater = 12;
				$lesser = 10;
				break;
			
			default:
				// return $this->invalid_quarter($month);
				break;
		}

		return $this->set_date($year, $lesser, $year, $greater);
	}

	public function set_date_range($type, $year, $month=0, $year2=0, $month2=0)
	{
		$dates = [];
		if($type == 5){
			if($year > $year2) return ['error' => 'From year is greater'];
			if($year == $year2 && $month > $month2 ) return ['error' => 'From month is greater'];

			$dates = $this->set_date($year, $month, $year2, $month2);
		}
		else if($type == 4) $dates = $this->set_quarters($year, $month);
		else if($type == 3) $dates = $this->set_date($year, $month, $year, $month);
		else if($type == 1) $dates = $this->set_date($year, 1, $year, 12);
		else{
	        $message = 'Type ' . $type . ' is invalid. Value must be between 1 and 5. 1 is for the total for the whole year. 2 is for the year with data grouped by month. 3 is for the a particular month. 4 for a particular quarter. 5 for a date range.';
	        return ['error' => $message];
		}

		return " datetested BETWEEN '" . $dates[0] . "' AND '" . $dates[1] . "' ";
	}


}