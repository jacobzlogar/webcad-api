<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeChescoIncidents implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $results;
    public function __construct()
    {
        $this->results = [];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $res = Http::withOptions([
            'verify' => false,
            'debug' => true,
        ])->get(config('webcad.url'));
        $doc = new \DOMDocument();

        libxml_use_internal_errors(true);

        $doc->loadHtml($res->body());

        $rows = $doc->getElementsByTagName('tr');

        foreach ($rows as $row) {
            $r = preg_replace('/[\n]/', ',', $row->textContent);
            $r = preg_replace('/[\t\r]/', '', $r);
            $columns = explode(",", $r);
            $this->results += $columns;
        }
        dump($this->results);
        //dd($doc);
    }
}
