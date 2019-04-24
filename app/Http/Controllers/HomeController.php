<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    //

    public function home(){
    	// $path = storage_path('app/API_Documentation.docx');
    	$path = public_path('API_Documentation.docx');
    	return response()->download($path);
    }
}
