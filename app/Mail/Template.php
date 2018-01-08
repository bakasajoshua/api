<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Template extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $filePath = storage_path('exports/Confirmatory_Report.csv');
        $filePath2 = storage_path('exports/Confirmatory_Negatives.csv');
        $filePath3 = storage_path('exports/Confirmatory_Tests_Without_Previous_Test.csv');
        $filePath4 = storage_path('exports/Patients_With_Multiple_Confirmatory_Tests.csv');
        return $this->view('report')->attach($filePath)->attach($filePath2)->attach($filePath3)->attach($filePath4);
    }
}
