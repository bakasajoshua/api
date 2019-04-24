<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\EidReport;
use App\Mail\Template;

use DB;
use Excel;
// use Carbon/Carbon;

class Eid extends Model
{
    //
    public function positives_report($year=null, $month=null){
    	echo "Method start \n";
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = "samples.ID, samples.patient, samples.facility, labs.name as lab, view_facilitys.name as facility_name, samples.pcrtype, datetested";
    	$raw2 = "samples.ID, samples.patient, samples.facility, samples.pcrtype, datetested";

    	$data = DB::connection('eid')
		->table("samples")
		->select(DB::raw($raw))
		->join('view_facilitys', 'samples.facility', '=', 'view_facilitys.ID')
		->join('labs', 'samples.labtestedin', '=', 'labs.ID')
		->orderBy('samples.facility', 'desc')
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', 2)
		->where('samples.repeatt', 0)
		->where('samples.Flag', 1)
		->where('samples.eqa', 0)
		->get();

		// return $data;


		$i = 0;
		$result = null;

		foreach ($data as $patient) {
			

	    	$d = DB::connection('eid')
			->table("samples")
			->select(DB::raw($raw2))
			->where('facility', $patient->facility)
			->where('patient', $patient->patient)
			->where('datetested', '<', $patient->datetested)
			->where('result', 1)
			->where('repeatt', 0)
			->where('Flag', 1)
			->where('eqa', 0)
			->first();

			if($d){
				$result[$i]['laboratory'] = $patient->lab;
				$result[$i]['facility'] = $patient->facility;
				$result[$i]['patient_id'] = $patient->patient;
				$result[$i]['negative_sample_id'] = $d->ID;
				$result[$i]['negative_date'] = $d->datetested;
				$result[$i]['negative_pcr'] = $d->pcrtype;
				$result[$i]['positive_sample_id'] = $patient->ID;
				$result[$i]['positive_date'] = $patient->datetested;
				$result[$i]['positive_pcr'] = $patient->pcrtype;
				$i++;

				echo "Found 1 \n";
				$d = null;
			}


		}

		Excel::create('Negative_to_Positive', function($excel) use($result)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($result) {

		        $sheet->fromArray($result);

		    });

		})->store('csv');

		// return $result;
    }

    public function negatives_report($year=null, $month=null){
    	echo "Method start \n";
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = "samples.ID, samples.patient, samples.facility, labs.name as lab, view_facilitys.name as facility_name, samples.pcrtype,  datetested";
    	$raw2 = "samples.ID, samples.patient, samples.facility, samples.pcrtype, datetested";

    	$data = DB::connection('eid')
		->table("samples")
		->select(DB::raw($raw))
		->join('view_facilitys', 'samples.facility', '=', 'view_facilitys.ID')
		->join('labs', 'samples.labtestedin', '=', 'labs.ID')
		->orderBy('samples.facility', 'desc')
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', 1)
		->where('samples.repeatt', 0)
		->where('samples.Flag', 1)
		->where('samples.eqa', 0)
		->get();

		echo "Total {$data->count()} \n";

		$i = 0;
		$result = null;

		foreach ($data as $patient) {

	    	$d = DB::connection('eid')
			->table("samples")
			->select(DB::raw($raw2))
			->where('facility', $patient->facility)
			->where('patient', $patient->patient)
			->where('datetested', '<', $patient->datetested)
			->where('result', 2)
			->where('repeatt', 0)
			->where('Flag', 1)
			->where('eqa', 0)
			->first();

			if($d){
				$result[$i]['laboratory'] = $patient->lab;
				$result[$i]['facility'] = $patient->facility;
				$result[$i]['patient_id'] = $patient->patient;

				$result[$i]['negative_sample_id'] = $patient->ID; 
				$result[$i]['negative_date'] = $patient->datetested;
				$result[$i]['negative_pcr'] = $patient->pcrtype;

				$result[$i]['positive_sample_id'] = $d->ID;
				$result[$i]['positive_date'] =  $d->datetested;
				$result[$i]['positive_pcr'] = $d->pcrtype;
				$i++;

				echo "Found 1 \n";
				$d = null;
			}


		}

		Excel::create('Positive_to_Negative', function($excel) use($result)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($result) {

		        $sheet->fromArray($result);

		    });

		})->store('csv');

		// return $result;
    }


    public function confirmatory_report(){

    	$raw = "samples.ID, samples.patient, samples.facility, patients.age, labs.name as lab, view_facilitys.name as facility_name, view_facilitys.facilitycode as facilitycode, view_facilitys.countyname, view_facilitys.subcounty, view_facilitys.partnername, samples.pcrtype, datetested, datecollected, results.Name as test_result";
    	$raw2 = "samples.ID, samples.patient, samples.facility, samples.pcrtype, datetested";

    	$data = DB::connection('eid')
		->table("samples")
		->select(DB::raw($raw))
		->join('view_facilitys', 'samples.facility', '=', 'view_facilitys.ID')
		->join('patients', 'samples.patientautoid', '=', 'patients.autoID')
		->join('labs', 'samples.labtestedin', '=', 'labs.ID')
		->join('results', 'samples.result', '=', 'results.ID')
		->orderBy('samples.facility', 'desc')
		->whereYear('datetested', '>', 2016)
		->where('pcrtype', 3)
		->where('samples.repeatt', 0)
		->where('samples.Flag', 1)
		->where('samples.facility', '!=', 7148)
		->get();

		echo "Begin confirmatory report \n";
		echo "Total {$data->count()} \n";

		$i = 0;
		$result = null;

		foreach ($data as $patient) {

	    	$d = DB::connection('eid')
			->table("samples")
			->select(DB::raw($raw2))
			->where('facility', $patient->facility)
			->where('patient', $patient->patient)
			->whereDate('datetested', '<', $patient->datetested)
			->where('result', 1)
			->where('repeatt', 0)
			->where('Flag', 1)
			->where('eqa', 0)
			->where('pcrtype', '<', 3)
			->first();

			if($d == null){
				$result[$i]['laboratory'] = $patient->lab;
				$result[$i]['facilityMFLCode'] = $patient->facilitycode;
				$result[$i]['facility'] = $patient->facility_name;
				$result[$i]['patient_age'] = $patient->age;
				$result[$i]['subcounty'] = $patient->subcounty;
				$result[$i]['county'] = $patient->countyname;
				$result[$i]['partner'] = $patient->partnername;
				$result[$i]['patient_id'] = $patient->patient;

				$result[$i]['sample_id'] = $patient->ID; 
				$result[$i]['date_of_test'] = $patient->datetested;
				$result[$i]['date_collected'] = $patient->datecollected;
				$result[$i]['result'] = $patient->test_result;
				$i++;

				$d = null;
			}


		}

		echo "Found {$i} records \n";

		Excel::create('Confirmatories_Without_Previous_Positive', function($excel) use($result)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($result) {

		        $sheet->fromArray($result);

		    });

		})->store('csv');
    }

    public function confirmatory_report_two(){

    	$raw = "samples.ID, samples.patient, samples.facility, patients.age, labs.name as lab, view_facilitys.name as facility_name, view_facilitys.facilitycode as facilitycode, view_facilitys.countyname, view_facilitys.subcounty, view_facilitys.partnername, samples.pcrtype, datetested, datecollected, results.Name as test_result";
    	$raw2 = "samples.ID, samples.patient, samples.facility, samples.pcrtype, datetested";

    	$data = DB::connection('eid')
		->table("samples")
		->select(DB::raw($raw))
		->join('view_facilitys', 'samples.facility', '=', 'view_facilitys.ID')
		->join('patients', 'samples.patientautoid', '=', 'patients.autoID')
		->join('labs', 'samples.labtestedin', '=', 'labs.ID')
		->join('results', 'samples.result', '=', 'results.ID')
		->orderBy('samples.facility', 'desc')
		->whereYear('datetested', '>', 2016)
		->where('pcrtype', 3)
		->where('samples.repeatt', 0)
		->where('samples.Flag', 1)
		->where('samples.facility', '!=', 7148)
		->get();

		echo "Begin confirmatory report two \n";
		echo "Total {$data->count()} \n";

		$i = 0;
		$result = null;

		foreach ($data as $patient) {

	    	$d = DB::connection('eid')
			->table("samples")
			->select(DB::raw($raw2))
			->where('facility', $patient->facility)
			->where('patient', $patient->patient)
			->whereDate('datetested', '<', $patient->datetested)
			->whereBetween('result', [1, 2])
			->where('repeatt', 0)
			->where('Flag', 1)
			->where('eqa', 0)
			->where('pcrtype', '<', 3)
			->first();

			if($d == null){
				$result[$i]['laboratory'] = $patient->lab;
				$result[$i]['facilityMFLCode'] = $patient->facilitycode;
				$result[$i]['facility'] = $patient->facility_name;
				$result[$i]['patient_age'] = $patient->age;
				$result[$i]['subcounty'] = $patient->subcounty;
				$result[$i]['county'] = $patient->countyname;
				$result[$i]['partner'] = $patient->partnername;
				$result[$i]['patient_id'] = $patient->patient;

				$result[$i]['sample_id'] = $patient->ID; 
				$result[$i]['date_of_test'] = $patient->datetested;
				$result[$i]['date_collected'] = $patient->datecollected;
				$result[$i]['result'] = $patient->test_result;
				$i++;

				$d = null;
			}


		}

		echo "Found {$i} records \n";

		Excel::create('Confirmatory_Tests_Without_Previous_Test', function($excel) use($result)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($result) {

		        $sheet->fromArray($result);

		    });

		})->store('csv');
    }

    public function confirmatory_multiple(){

    	$raw = "count(*) as `tests`, samples.patient, samples.facility, patients.age, labs.name as lab, view_facilitys.name as facility_name, view_facilitys.facilitycode as facilitycode, view_facilitys.countyname as county, view_facilitys.subcounty, view_facilitys.partnername as partner";

    	$data = DB::connection('eid')
		->table("samples")
		->select(DB::raw($raw))
		->join('view_facilitys', 'samples.facility', '=', 'view_facilitys.ID')
		->join('patients', 'samples.patientautoid', '=', 'patients.autoID')
		->join('labs', 'samples.labtestedin', '=', 'labs.ID')
		->join('results', 'samples.result', '=', 'results.ID')
		->orderBy('samples.facility', 'desc')
		->whereYear('datetested', '>', 2016)
		->where('pcrtype', 3)
		->where('samples.repeatt', 0)
		->where('samples.Flag', 1)
		->where('samples.facility', '!=', 7148)
		->groupBy('samples.patient', 'samples.facility')
		->having('tests', '>', 1)
		->get()->toArray();

		$result = null;


		foreach ($data as $key => $value) {
			$value = collect($value);
			$result[$key] = $value->toArray();
		}

		Excel::create('Patients_With_Multiple_Confirmatory_Tests', function($excel) use($result)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($result) {

		        $sheet->fromArray($result);

		    });

		})->store('csv');
    }

    public function confirmatory_positives_report(){

    	$raw = "samples.ID, samples.patient, samples.facility, patients.age, labs.name as lab, view_facilitys.name as facility_name, samples.pcrtype,  datetested, datecollected";
    	$raw2 = "samples.ID, samples.patient, samples.facility, samples.pcrtype, datetested";

    	$data = DB::connection('eid')
		->table("samples")
		->select(DB::raw($raw))
		->join('view_facilitys', 'samples.facility', '=', 'view_facilitys.ID')
		->join('patients', 'samples.patientautoid', '=', 'patients.autoID')
		->join('labs', 'samples.labtestedin', '=', 'labs.ID')
		->orderBy('samples.facility', 'desc')
		->whereYear('datetested', '>', 2016)
		->where('result', 1)
		->where('pcrtype', 3)
		->where('samples.repeatt', 0)
		->where('samples.Flag', 1)
		->where('samples.facility', '!=', 7148)
		->get()->toArray();

		$result = null;


		foreach ($data as $key => $value) {
			$value = collect($value);
			$result[$key] = $value->toArray();
		}

		// dd($result);

		// $out = fopen('php://memory', 'w');
		// fputcsv($out, array_keys($data[1]));

		// foreach ($data as $value) {
		// 	fputcsv($out, $value);
		// }
		// fseek($out, 0);



	 //    header('Content-Type: application/csv');
	 //    header('Content-Disposition: attachement; filename="Confirmatory_Negatives.csv";');

		Excel::create('Confirmatory_Negatives', function($excel) use($result)  {

		    // Set sheets

		    $excel->sheet('Sheetname', function($sheet) use($result) {

		        $sheet->fromArray($result);

		    });

		})->store('csv');
    }



    public function report($year=null, $month=null){
    	if($year==null){
    		$year = Date('Y');
    	}

    	$raw = "samples.patient, samples.facility, labs.name as lab, view_facilitys.name as facility, samples.pcrtype,  datetested";

    	// $data = 

    	$positives = DB::connection('eid')
		->table("samples")
		->select(DB::raw($raw))
		->join('view_facilitys', 'samples.facility', '=', 'view_facilitys.ID')
		->join('labs', 'samples.labtestedin', '=', 'labs.ID')
		->orderBy('facility', 'desc')
		->groupBy('patient', 'facility') 
		->whereYear('datetested', $year)
		->when($month, function($query) use ($month){
			if($month != null || $month != 0){
				return $query->whereMonth('datetested', $month);
			}
		})
		->where('result', 2)
		->where('samples.repeatt', 0)
		->where('samples.Flag', 1)
		->where('samples.eqa', 0)
		->get();

	}

    public function send_report(){
    	$mail_array = array('joelkith@gmail.com', 'tngugi@gmail.com', 'jbatuka@usaid.gov');
    	$up = new EidReport;
    	Mail::to($mail_array)->send($up);
    }

    public function send_confirm(){
    	$mail_array = array('joelkith@gmail.com', 'tngugi@gmail.com', 'baksajoshua09@gmail.com', 'jbatuka@usaid.gov');
    	$up = new Template;
    	Mail::to($mail_array)->send($up);
    }
}
