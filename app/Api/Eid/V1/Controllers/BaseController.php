<?php

namespace App\Api\Eid\V1\Controllers;



use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BaseController extends Controller
{
	use Helpers;

	protected $county_string = 'countys.ID as county_id, CountyDHISCode as CountyDHISCode, CountyMFLCode as CountyMFLCode, countys.name as County, ';

	protected $subcounty_string = 'districts.ID as subcounty_id, districts.SubCountyDHISCode as SubCountyDHISCode, districts.SubCountyMFLCode as SubCountyMFLCode, districts.name as Subcounty, ';

	protected $site_string = 'facilitys.ID as facility_id, facilitys.facilitycode as facilityMFLCode, facilitys.DHIScode as facilityDHISCode, facilitys.name as facility, ';

	protected $partner_string = 'partners.ID as Partner_id, partners.name as Partner, ';

	protected $lab_string = 'labs.ID as lab_id, labs.name as Lab, ';

	protected $patient_string = 'count(*) as `tests`, FacilityMFLcode, patientID';

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

	protected function summary_query(){
		return 'SUM(alltests) as `Total Tests`,
		 SUM(pos) as `Positive`, 
		 SUM(neg) as `Negative`,
		  SUM(redraw) as `Redraws`, 
		  SUM(rejected) as `Rejected`, 
		  SUM(firstdna) as `First DNA PCR With Valid Results`, 
		  SUM(confirmdna) as `Repeat Positive Confirmatory Tests`, 
		  AVG(sitessending) as `Sites Sending`, 
		  AVG(medage) as `Median Age of Testing`, 
		   SUM(actualinfants) as infants, 
		   SUM(actualinfantsPOS) as infants_positive,
		    SUM(infantsless2m) as infants_less_2m, 
		    SUM(adults) as adults, 
		    SUM(adultsPOS) as `adults positive`,
		     AVG(tat1) as `collection to lab receipt`, 
		     AVG(tat2) as `lab receipt to testing`, 
		     AVG(tat3) as `tested to dispatch`, 
		     AVG(tat4) as `collection to dispatch`';
	}

	protected function partner_summary_query(){
		return 'SUM(alltests) as all_tests,
		 SUM(pos) as positives, 
		 SUM(neg) as negatives,
		  SUM(redraw) as redraws, 
		  SUM(rejected) as rejected,
		  SUM(firstdna) as first_DNA_PCR, 
		  SUM(confirmdna) as repeat_confirmatory_PCR, 
		  AVG(sitessending) as sites_sending, 
		  AVG(medage) as median_age, 
		   SUM(actualinfants) as infants, 
		   SUM(actualinfantsPOS) as infants_positive,
		    SUM(infantsless2m) as infants_less_2m, 
		    SUM(adults) as adults';
	}

	protected function lab_summary_query(){
		return 'SUM(alltests) as all_tests,
		 SUM(pos) as positives, 
		 SUM(neg) as negatives,
		  SUM(redraw) as redraws, 
		  SUM(confirmdna) as repeat_confirmatory_PCR, 
		  AVG(sitessending) as sites_sending, 
		  SUM(rejected) as rejected,
		  SUM(eqatests) as eqa,
		  SUM(batches) as batches,
		  SUM(received) as `received samples`,
		     AVG(tat1) as `collection to lab_receipt`, 
		     AVG(tat2) as `lab receipt to testing`, 
		     AVG(tat3) as `tested to dispatch`, 
		     AVG(tat4) as `collection to dispatch`';
	}

	protected function hei_validation_query(){
		return 'SUM(validation_confirmedpos) as confirmed_pos,
		 SUM(validation_repeattest) as repeat_test, 
		 SUM(validation_viralload) as viral_load,
		  SUM(validation_adult) as adult, 
		  SUM(validation_unknownsite) as unknown_facility';
	}

	protected function hei_outcomes_query(){
		return 'SUM(ltfu) as lost_to_follow_up,
		 SUM(dead) as dead, 
		 SUM(adult) as adult,
		  SUM(transout) as transferred_out, 
		  SUM(other) as other';
	}


	protected function age_breakdown_query(){

		return 'SUM(pos) as positives, 
		 SUM(neg) as negatives,
		  age_bands.name as age_range';

		// return 'SUM(nodatapos) as no_data_pos,
		//  SUM(nodataneg) as no_data_neg, 
		//  SUM(sixweekspos) as six_weeks_pos, 
		//  SUM(sixweeksneg) as six_weeks_neg, 
		//  SUM(sevento3mpos) as seven_to_3m_pos, 
		//  SUM(sevento3mneg) as seven_to_3m_neg, 
		//  SUM(threemto9mpos) as three_to_9m_pos, 
		//  SUM(threemto9mneg) as three_to_9m_neg, 
		//  SUM(ninemto18mpos) as nine_to_18m_pos, 
		//  SUM(ninemto18mneg) as nine_to_18m_neg, 
		//  SUM(above18mpos) as above_18m_pos, 
		//  SUM(above18mneg) as above_18m_neg';
	}


	protected function entry_point_query(){
		return 'SUM(pos) as positives, 
		 SUM(neg) as negatives,
		  entry_points.name as EntryPoint';
	}

	protected function mother_prophylaxis_query(){
		return 'SUM(pos) as positives, 
		 SUM(neg) as negatives,
		  prophylaxis.name as MotherProphylaxis';
	}

	protected function infant_prophylaxis_query(){
		return 'SUM(pos) as positives, 
		 SUM(neg) as negatives,
		  prophylaxis.name as InfantProphylaxis';
	}

	protected function patient_query(){
		return 'PatientID, facilitys.name as Facility, districts.name as Subcounty, countys.name as County, partners.name as Partner, Age, Gender, mp.name as MotherProphylaxis, ip.name as InfantProphylaxis, entry_points.name as EntryPoint, feedings.name as FeedingType, datecollected, datereceived, datetested, results.Name as Result, datedispatched, labtestedin, labs.name as Lab, rejectedreasons.Name as RejectedReason, receivedstatus.name as ReceivedStatus';
	}


	protected function output_data($data, $type, $year, $month=NULL, $year2=NULL, $month2=NULL){
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

	public function set_date($year, $month, $year2, $month2)
	{
		$min = $year . '-' . $month . '-01';

		$max = Carbon::createFromFormat('Y-m-d', $year2 . '-' . ($month2+1) . '-01')->subDay()->toDateString();

		return array($min, $max);
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