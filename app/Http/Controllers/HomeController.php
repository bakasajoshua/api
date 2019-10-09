<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{
    private $testtypes = [
            'vl' => 'viralsamples_view',
            'eid' => 'samples_view',
        ];

    public function home(){
    	// $path = storage_path('app/API_Documentation.docx');
    	$path = public_path('API_Documentation.docx');
    	return response()->download($path);
    }

    public function rtk(Request $request) 
    {
        $return = [];
        if ($request->has('test')) {
            $testtype = strtolower($request->input('test'));
            if (!isset($this->testtypes[$testtype]))
                goto next;
            $table = $this->testtypes[$testtype];
            $return[$testtype] = DB::connection('national')->table($table)->join('view_facilitys', 'view_facilitys.id', '=', "{$table}.facility_id")->where('site_entry', '=', 2)->whereYear('datetested','2019')->groupBy('facilitycode')->groupBy('facilityname')->orderBy('tests', 'desc')->selectRaw("view_facilitys.facilitycode,view_facilitys.name AS `facilityname`,COUNT($table.id) AS `tests`")->get();
        } else {
            next:
            foreach ($this->testtypes as $key => $value) {
                $return[$key] = DB::connection('national')->table($value)->join('view_facilitys', 'view_facilitys.id', '=', "{$value}.facility_id")->where('site_entry', '=', 2)->whereYear('datetested','2019')->groupBy('facilitycode')->groupBy('facilityname')->orderBy('tests', 'desc')->selectRaw("view_facilitys.facilitycode,view_facilitys.name AS `facilityname`,COUNT({$value}.id) AS `tests`")->get();
            }
        }
    	return response()->json($return);
    }

    private function getQuery($conn, $table)
    {
        return $conn->selectRaw("view_facilitys.facilitycode,view_facilitys.name AS `facilityname`,COUNT(samples_view.id) AS `tests`");
    }
}
