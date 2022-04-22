<?php

namespace App\Http\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use DB;
use Illuminate\Support\Facades\Log;


class GoogleSheetsServices
{

    public $client, $service, $documentId, $range;

    public function __construct($user)
    {
        $this->client = $this->getClient();
        $this->service = new Sheets($this->client);
        $gs_id = DB::table('campaign_googlesheet')->where('campaign_id', $user->campaign_id)->pluck('googlesheet_id')->first() ?? '10';
        Log::info('gs_id= ' . $gs_id);
        $gs_document = DB::table('googlesheets')->where('id',  $gs_id)->pluck('value_id')->first();
        Log::info('gs_document= ' . $gs_id);
        $this->documentId = $gs_document;
        $this->range = env('GOOGLE_RANGE');
    }
    public function getClient()
    {
        $client = new Client();
        $client->setApplicationName('Google Sheets Laravel');
        $client->setRedirectUri('https://www.mila.cpaservice.it/sheets');
        $client->setScopes(Sheets::SPREADSHEETS);
        $client->setAuthConfig('credentials.json');
        $client->setAccessType('offline');

        return $client;
    }

    public function readSheet()
    {
        $doc = $this->service->spreadsheets_values->get($this->documentId, $this->range);
        return $doc;
    }

    public function writeSheet($values)
    {
        $body = new ValueRange([
            'values' => $values
        ]);
        $params = [
            'valueInputOption' => 'RAW'
        ];
        $result = $this->service->spreadsheets_values->append(
            $this->documentId,
            $this->range,
            $body,
            $params
        );
    }
}
