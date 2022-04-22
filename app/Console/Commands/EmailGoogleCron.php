<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use App\Client;
use App\Http\Services\GoogleSheetsServices;
use App\Mail\SendNewMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\OrdersRow;

class EmailGoogleCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgoogle:mila';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $subtract = Carbon::now()->subMinutes(5);
        $users = Client::where('sentGoogleMail', 0)->where('cpa_customer', env('CPA_CUSTOMER'))->get();

        foreach ($users as $user) {
            //env('MAIL_TO_CPA_COSTUMER')
            if (env('CUSTOMER_REPUTATION') == 'bad') {
                $user->fullname = 'hidden';
                $user->email = 'hidden@mail.com';
                $user->phone = 'hidden';
                $user->city = 'hidden';
                $user->state = 'hidden';
                $user->address = 'hidden';
                $user->zipcode = 'hidden';
            }
            Log::info($user);
            if ($user->updated_at < $subtract) {
                if ($user->sentMail == 0) {
                    $order = OrdersRow::where('client_id', $user->id)->get();
                    Mail::to(env('MAIL_TO_CPA_COSTUMER'))
                        ->bcc(env('MAIL_TO_CPA'))
                        ->send(new SendNewMail($user, $order));

                    DB::table('clients')->where('id', $user->id)->update(['sentMail' => true]);
                    Log::info('Mail inviata');
                }

                if ($user->sentGoogle == 0) {
                    $items = $user->items;
                    $items_ar = explode(', ', $items);

                    (new GoogleSheetsServices($user))->writeSheet([
                        [
                            $user->created_at->format('d/m/Y'),
                            $user->created_at->format('H:i'),
                            $user->id_invoice ?? '',
                            $user->cod_campaign ?? '', // Codice Campagna
                            substr($user->cod_campaign, -1) ?? '', // Landing Type
                            $user->fullname ?? '',
                            $user->email,
                            $user->phone ?? '',
                            $user->state ?? '',
                            $user->country ?? '',
                            $user->city ?? '',
                            $user->zipcode ?? '',
                            $user->address ?? '',
                            $user->items ?? '',
                            count($items_ar) ?? '',
                            $user->total ?? '',
                            $user->payment_method ?? '',
                            $user->paid ?? '',
                            $user->privacy ?? 'NO',
                            $user->id_transaction ?? '',
                        ]
                    ]);

                    DB::table('clients')->where('id', $user->id)->update(['sentGoogle' => true]);
                    Log::info('Cliente scritto su gsheet');
                }

                Log::info('sta completando');
                DB::table('clients')->where('id', $user->id)->update(['sentGoogleMail' => true]);
                Log::info('cliente completato');
            }
        }
    }
}
