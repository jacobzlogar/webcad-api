<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Crawler
{
    public function buildColumns($row, $columns = [])
    {
        $cells = $row->getElementsByTagName('td');

        foreach ($cells as $cell) {
            $columns[] = $cell->textContent;
        }

        return $columns;
    }

    public function buildCells($row, $columns, $incidents = [])
    {
        $cells = $row->getElementsByTagName('td');

        foreach ($cells as $key => $cell) {
            if (! $key) {
                $anchor = $cell->getElementsByTagName('a')->item(0); 
                $href = $anchor->attributes->getNamedItem('href')->textContent;
                $incidents[$columns[$key]] = [
                    'url' => config('webcad.comments-uri').urlencode($href),
                    'number' => $cell->textContent
                ];
            } else {
                $incidents[$columns[$key]] = $cell->textContent;
            }
        }

        return $incidents;
    }

    public function getDetails($incident, $columns = [], $results = [])
    {
        https://webcad.chesco.org/WebCad/livecadcomments-traffic.asp%3Feid%3D1726124%26incidentno%3DP21014730%26incidenttype%3DACCIDENT+HIT+%26+RUN+NO+INJURY%26station%3D25+%26location%3DEVERGREEN+ST+%2F+OAKLAND+AVE%26mun%3DWGROVE
        $res = Http::withOptions([
            'verify' => false,
            'debug' => true,
        ])->get($incident['Incident No.']['url']);

        $doc = new \DOMDocument();

        libxml_use_internal_errors(true);

        $doc->loadHtml($res->body());

        $tables = $doc->getElementsByTagName('table');

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('td');
            foreach ($rows as $key => $row) {
                if (! $key) {
                    $columns[] = $row->textContent;
                }
            }
        }
        dump($columns);
    }
}
