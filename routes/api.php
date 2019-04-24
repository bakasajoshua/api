<?php

use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

$api->version('v1', function (Router $api) {
    // $api->get('/', function(){ return JWTAuth::parseToken()->authenticate(); });

    $api->group(['prefix' => 'auth'], function(Router $api) {
        $api->group(['prefix' => 'ver2.0', 'namespace' => 'App\\Api\\Auth\\V1\\Controllers'], function(Router $api) {

            $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
                $api->get('users', 'SignUpController@users');
                $api->get('user_types', 'SignUpController@user_types');

                $api->post('profile', 'SignUpController@profile');
            });



            $api->post('signup', 'SignUpController@signUp');
            $api->post('login', 'LoginController@login');
            $api->post('recovery', 'ForgotPasswordController@sendResetEmail');
            $api->post('reset', 'ResetPasswordController@resetPassword');
            // $api->get('test', 'SignUpController@test');
            // $api->get('test2', 'SignUpController@test2');
        });
        
    });



//$api->group(['middleware' => 'api.auth'], function(Router $api) {
// $api->group(['middleware' => 'api.throttle', 'limit' => 5, 'expires' => 1], function(Router $api) {

$api->get('/', 'App\Http\Controllers\HomeController@home');

$api->group(['prefix' => 'eid'], function(Router $api) {
    $api->group(['prefix' => 'ver2.0', 'namespace' => 'App\\Api\\Eid\\V1\\Controllers'], function(Router $api) {

        $api->group(['middleware' => 'api.throttle', 'limit' => 100, 'expires' => 1], function(Router $api) {

            $api->get('/', function(){ return 'Hello World'; }); 

            $api->group(['prefix' => 'national'], function(Router $api) {

                $api->get('summary/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@summary');

                $api->get('hei/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@hei_outcomes');

                $api->get('hei_validation/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@hei_validation');

                $api->get('age_breakdown/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@age_breakdown');

                $api->get('entry_point/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@entry_point');

                $api->get('mprophylaxis/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@mother_prophylaxis');

                $api->get('iprophylaxis/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@infant_prophylaxis');
            });

            $api->group(['prefix' => 'county'], function(Router $api) {

                $api->get('counties', 'CountyController@counties');

                $api->get('info/{county}/', 'CountyController@info');

                $api->get('summary/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@summary');

                $api->get('hei/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@hei_outcomes');

                $api->get('hei_validation/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@hei_validation');

                $api->get('age_breakdown/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@age_breakdown');

                $api->get('entry_point/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@entry_point');

                $api->get('mprophylaxis/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@mother_prophylaxis');

                $api->get('iprophylaxis/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@infant_prophylaxis');

                $api->get('facilities/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@county_sites');
            });


            $api->group(['prefix' => 'subcounty'], function(Router $api) {

                $api->get('subcounties', 'SubcountyController@subcounties');

                $api->get('info/{subcounty}/', 'SubcountyController@info');

                $api->get('summary/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@summary');

                $api->get('hei/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@hei_outcomes');

                $api->get('hei_validation/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@hei_validation');

                $api->get('age_breakdown/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@age_breakdown');

                $api->get('entry_point/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@entry_point');

                $api->get('mprophylaxis/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@mother_prophylaxis');

                $api->get('iprophylaxis/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@infant_prophylaxis');

                $api->get('facilities/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@subcounty_sites');
            });

            $api->group(['prefix' => 'partner'], function(Router $api) {

                $api->get('partners', 'PartnerController@partners');

                $api->get('info/{partner}/', 'PartnerController@info');

                $api->get('summary/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@summary');

                $api->get('hei/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@hei_outcomes');

                $api->get('hei_validation/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@hei_validation');

                $api->get('age_breakdown/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@age_breakdown');

                $api->get('entry_point/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@entry_point');

                $api->get('mprophylaxis/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@mother_prophylaxis');

                $api->get('iprophylaxis/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@infant_prophylaxis');

                $api->get('facilities/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@partner_sites');
            });

            $api->group(['prefix' => 'lab'], function(Router $api) {

                $api->get('labs', 'LabController@labs');

                $api->get('info/{lab}/', 'LabController@info');

                $api->get('summary/{lab}/{type}/{year}/{month?}/{year2?}/{month2?}', 'LabController@summary');

            });

            $api->group(['prefix' => 'facility'], function(Router $api) {

                $api->get('facilities', 'SiteController@sites');

                $api->get('unsupported_facilities', 'SiteController@unsupported_sites');

                $api->get('info/{site}/', 'SiteController@info');

                $api->get('summary/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SiteController@summary');

                $api->get('hei/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SiteController@hei_outcomes');

                $api->get('hei_validation/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SiteController@hei_validation');

            });

            $api->group(['middleware' => 'jwt.auth'], function(Router $api) {

                $api->group(['prefix' => 'report'], function(Router $api) {

                    $api->get('positives/{year?}/{month?}', 'ReportController@positives_report');

                    $api->get('negatives/{year?}/{month?}', 'ReportController@negatives_report');

                });

            });

        });

        $api->group(['prefix' => 'patient'], function(Router $api) {
            $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
                $api->get('results/{site}/{patientID}', 'PatientController@get_results')->where('patientID', '(.*)');
            });

            $api->get('national/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@national_tests');

            $api->get('county/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@county_tests');

            $api->get('subcounty/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@subcounty_tests');

            $api->get('facility/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@facility_tests');

            $api->get('partner/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@partner_tests');

        });

        $api->group(['prefix' => 'patient2'], function(Router $api) {

            $api->get('national/{type}/{pcrtype}/{age}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@national_tests2');

            $api->get('county/{county}/{type}/{pcrtype}/{age}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@county_tests2');

            $api->get('subcounty/{subcounty}/{type}/{pcrtype}/{age}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@subcounty_tests2');

            $api->get('facility/{site}/{type}/{pcrtype}/{age}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@facility_tests2');

            $api->get('partner/{site}/{type}/{pcrtype}/{age}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@partner_tests2');

        });

        $api->group(['prefix' => 'patient3'], function(Router $api) {

            $api->get('national/{type}/{pcrtype}/{age_lower}/{age_upper}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@national_tests3');

            $api->get('county/{county}/{type}/{pcrtype}/{age_lower}/{age_upper}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@county_tests3');

            $api->get('subcounty/{subcounty}/{type}/{pcrtype}/{age_lower}/{age_upper}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@subcounty_tests3');

            $api->get('facility/{site}/{type}/{pcrtype}/{age_lower}/{age_upper}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@facility_tests3');

            $api->get('partner/{site}/{type}/{pcrtype}/{age_lower}/{age_upper}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@partner_tests3');

        });
        
    });
});




$api->group(['prefix' => 'vl'], function(Router $api) {
    $api->group(['prefix' => 'ver2.0', 'namespace' => 'App\\Api\\Vl\\V1\\Controllers'], function(Router $api) {

        $api->group(['middleware' => 'api.throttle', 'limit' => 100, 'expires' => 1], function(Router $api) {
        
            $api->get('/', function(){ return 'Hello World'; });

            $api->group(['prefix' => 'national'], function(Router $api) {

                $api->get('summary/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@summary');

                $api->get('sample_type/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@sample_type');

                $api->get('regimen/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@regimen');

                $api->get('justification/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@justification');

                $api->get('gender/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@gender');

                $api->get('age/{type}/{year}/{month?}/{year2?}/{month2?}', 'NationalController@age');

            });

            $api->group(['prefix' => 'county'], function(Router $api) {

                $api->get('counties', 'CountyController@counties');

                $api->get('info/{county}/', 'CountyController@info');

                $api->get('summary/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@summary');

                $api->get('regimen/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@regimen');

                $api->get('gender/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@gender');

                $api->get('age/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@age');

                $api->get('facilities/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'CountyController@county_sites');
            });


            $api->group(['prefix' => 'subcounty'], function(Router $api) {

                $api->get('subcounties', 'SubcountyController@subcounties');

                $api->get('info/{subcounty}/', 'SubcountyController@info');

                $api->get('summary/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@summary');

                $api->get('regimen/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@regimen');

                $api->get('gender/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@gender');

                $api->get('age/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@age');

                $api->get('facilities/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SubcountyController@subcounty_sites');
            });

            $api->group(['prefix' => 'partner'], function(Router $api) {

                $api->get('partners', 'PartnerController@partners');

                $api->get('info/{partner}/', 'PartnerController@info');

                $api->get('summary/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@summary');

                $api->get('justification/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@justification');

                $api->get('gender/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@gender');

                $api->get('age/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@age');

                $api->get('facilities/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PartnerController@partner_sites');
            });

            $api->group(['prefix' => 'lab'], function(Router $api) {

                $api->get('labs', 'LabController@labs');

                $api->get('info/{lab}/', 'LabController@info');

                $api->get('summary/{lab}/{type}/{year}/{month?}/{year2?}/{month2?}', 'LabController@summary');

            });

            $api->group(['prefix' => 'facility'], function(Router $api) {

                $api->get('facilities', 'SiteController@sites');

                $api->get('unsupported_facilities', 'SiteController@unsupported_sites');

                $api->get('info/{site}/', 'SiteController@info');

                $api->get('summary/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SiteController@summary');

                $api->get('regimen/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SiteController@regimen');

                $api->get('gender/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SiteController@gender');

                $api->get('age/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'SiteController@age');

            });

            $api->group(['middleware' => 'jwt.auth'], function(Router $api) {

                $api->group(['prefix' => 'report'], function(Router $api) {

                    $api->get('national/{year}/{month}', 'ReportController@nation_report');

                    $api->get('county/{county}/{year?}/{month?}', 'ReportController@county_report');

                    $api->get('subcounty/{subcounty}/{year?}/{month?}', 'ReportController@subcounty_report');

                    $api->get('facility/{site}/{year?}/{month?}', 'ReportController@site_report');

                    $api->get('partner/{partner}/{year?}/{month?}', 'ReportController@partner_report');

                });
            
            });

        });

        $api->group(['prefix' => 'patient'], function(Router $api) {
            $api->group(['middleware' => 'jwt.auth'], function(Router $api) {
                $api->get('results/{site}/{patientID}', 'PatientController@get_results')->where('patientID', '(.*)');
            });

            $api->get('viralloads', 'PatientController@viralloads');

            $api->get('national/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@national_viralloads');
            $api->get('county/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@county_viralloads');
            $api->get('subcounty/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@subcounty_viralloads');
            $api->get('facility/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@facility_viralloads');
            $api->get('partner/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@partner_viralloads');

            $api->group(['prefix' => 'suppression'], function(Router $api) {
                $api->get('national/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@national_suppression');
                $api->get('county/{county}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@county_suppression');
                $api->get('subcounty/{subcounty}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@subcounty_suppression');
                $api->get('facility/{site}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@facility_suppression');
                $api->get('partner/{partner}/{type}/{year}/{month?}/{year2?}/{month2?}', 'PatientController@partner_suppression');
            });

            $api->get('test', 'PatientController@test');

        });
        
    });
});

// });
//});


});

