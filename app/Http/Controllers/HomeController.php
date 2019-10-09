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
        $year = date('Y');
        if ($request->has('year'))
            $year = $request->input('year');
        if ($request->has('test')) {
            $testtype = strtolower($request->input('test'));
            if (!isset($this->testtypes[$testtype]))
                goto next;
            $table = $this->testtypes[$testtype];
            $return[$testtype]['data'] = DB::connection('national')->table($table)->join('view_facilitys', 'view_facilitys.id', '=', "{$table}.facility_id")->where('site_entry', '=', 2)->whereYear('datetested',$year)->groupBy('facilitycode')->when($request->has('facilitycode', function($query() use ($request){
                    return $query->where('view_facilitys.facilitycode', $request->input('facilitycode'));
                })))->when($request->has('month', function($query() use ($request){
                        return $query->whereMonth('datetested', $request->input('month'));
                })))->groupBy('facilityname')->orderBy('tests', 'desc')->selectRaw("view_facilitys.facilitycode,view_facilitys.name AS `facilityname`,COUNT($table.id) AS `tests`")->get();
        } else {
            next:
            foreach ($this->testtypes as $key => $value) {
                $return[$key]['data'] = DB::connection('national')->table($value)->join('view_facilitys', 'view_facilitys.id', '=', "{$value}.facility_id")->where('site_entry', '=', 2)->whereYear('datetested',$year)->when($request->has('facilitycode', function($query() use ($request){
                        return $query->where('view_facilitys.facilitycode', $request->input('facilitycode'));
                    })))->when($request->has('month', function($query() use ($request){
                        return $query->whereMonth('datetested', $request->input('month'));
                    })))->groupBy('facilitycode')->groupBy('facilityname')->orderBy('tests', 'desc')->selectRaw("view_facilitys.facilitycode,view_facilitys.name AS `facilityname`,COUNT({$value}.id) AS `tests`")->get();
                $return[$key]['test'] = $key;
                $return[$key]['year'] = $year;
                if ($request->has('month'))
                    $return[$key]['month'] = $request->input('month');
            }
        }
    	return response()->json($return);
    }
}
