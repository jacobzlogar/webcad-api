<?php

namespace App\Jobs;

use App\Models\Incident;
use App\Services\Crawler;
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
    public function handle(Crawler $crawler)
    {
        $res = Http::withOptions([
            'verify' => false,
            'debug' => true,
        ])->get(config('webcad.url'));

        $doc = new \DOMDocument();

        libxml_use_internal_errors(true);

        $doc->loadHtml($res->body());

        $tables = $doc->getElementsByTagName('table');

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $key => $row) {
                if (! $key) {
                    $columns = $crawler->buildColumns($row);
                } else {
                    $this->results[] = $crawler->buildCells($row, $columns);
                }
            }
        }

        foreach ($this->results as $result) {
            //dd($result['Dispatch Time']);
            $dispatched_at = \Carbon\Carbon::createFromFormat('m-d-Y H:i:s', $result['Dispatch Time']);

            $i = Incident::firstOrCreate([
                'incident_number' => $result['Incident No.']['number'],
            ], [
                'type' => $result['Incident Type'],
                'location' => $result['Incident Location'],
                'municipality' => $result['Municipality'],
                'dispatched_at' => $dispatched_at,
                'station' => $result['Station'] ?? $result['Agency'],
            ]);
        }
    }
}
