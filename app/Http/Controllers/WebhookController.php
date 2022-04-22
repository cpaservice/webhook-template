<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Client;
use Illuminate\Support\Facades\Log;
use App\Utm;
use App\Campaign;
use Carbon\Carbon;
use App\OrdersRow;
use App\CampaignsReference;
use Illuminate\Support\Facades\Hash as FacadesHash;


class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info(json_encode($request->all()));


        $tags = $request->tags;
        foreach ($tags as $tag) {
            if (str_contains($tag, 'cmp-')) {
                $id_campaign = explode('-', $tag)[1];
                $campaign = Campaign::where('id', $id_campaign)->pluck('cod_campaign')->first();
                // dd($campaign);
                if ($campaign) {
                    //
                } else {
                    $campaign = 9;
                }
            }
        }


        // Se è un LEAD (ebook)
        if (isset($request->website)) {
            $lead = new Client;
            // $id_campaign = Campaign::where('cod_campaign', $campaign)->pluck('id');
            $lead->campaign_id = $id_campaign ?? '';
            $lead->cod_campaign = $campaign ??  '';
            $lead->landing_type = substr($campaign, -1) ?? '';
            $lead->id_transaction = $request->contact['id'];
            $lead->fullname = $request->contact['name'];
            $lead->email = $request->contact['email'];
            $tags =  $request->contact['tags'];
            $lead->cpa_customer = env('CPA_CUSTOMER') ?? '';
            $tags = implode(", ", $tags);
            $lead->items = $tags;
            $lead->date = date('Y/m/d', $request->contact['createdOn']);
            $lead->sentGoogleMail = 1;
            $lead->note = 'LEAD';
            $lead->save();
        } else { // SE INVECE E' UN ORDINE

            $data = $request->all();
            $qty = count($request->items);
            $products = [];
            $items = $request->items;
            foreach ($items as $item) {
                $products[] = $item['name'];
            };
            $products = implode(", ", $products);

            // Se esiste giï¿½ il cliente lo aggiorno con i prodotti cross sell e qty
            if (Client::where('id_invoice', $request->invoiceNo)->where('cod_campaign', $campaign)->exists()) {
                Client::where('id_invoice', $request->invoiceNo)->where('cod_campaign', $campaign)->update(['items' => $products, 'qty' => $qty, 'total' => $request->total]);

                $current_client = Client::where('id_invoice', $request->invoiceNo)->where('cod_campaign', $campaign)->pluck('id')->first();
                $client = Client::where('id_invoice', $request->invoiceNo)->where('cod_campaign', $campaign)->first();
                OrdersRow::where('client_id', $current_client)->delete();
                if (count($items) > 0) { // Ricreo una riga di ogni prodotto per ciascun ordine
                    foreach ($items as $key => $item) {
                        $update_orderRow = new OrdersRow;
                        $update_orderRow->client_id = $client->id;
                        $update_orderRow->row_id = $key + 1;
                        $update_orderRow->product_id = $item['productId'];
                        $update_orderRow->name = $item['name'];
                        $update_orderRow->sku = $item['sku'];
                        $update_orderRow->qty = $item['quantity'];
                        $update_orderRow->total = $item['total'];
                        $update_orderRow->type = $item['type'];
                        $update_orderRow->shipping_required = $item['shippingRequired'];
                        $variations = $item['variation'];
                        $list_variations = [];
                        if (count($variations) > 0) {
                            foreach ($variations as $variation) {
                                $list_variations[] = $variation['name'];
                            }
                            $list_variations = implode(", ", $list_variations);
                            $update_orderRow->variations = $list_variations;
                        }
                        $additions = $item['additions'];
                        $list_additions = [];
                        if (count($additions) > 0) {
                            foreach ($additions as $variation) {
                                $list_additions[] = $variation['name'];
                            }
                            $list_additions = implode(", ", $list_additions);
                            $update_orderRow->additions = $list_additions;
                        }
                        $camp_ref = CampaignsReference::where('campaign_id', $client->campaign_id)->where('product_simvoly_id', $update_orderRow->product_id)->pluck('id');
                        if (count($camp_ref) > 0) {
                            $update_orderRow->camp_ref_id = $camp_ref[0];
                        }
                        $update_orderRow->save();
                    }
                }
                return response()->json([
                    'message' => 'Cliente aggiornato correttamente'
                ]);
            } else {
                // Se no ne creo uno nuovo
                $client = new Client;
                $client->id_invoice = $request->invoiceNo;
                $client->id_transaction = $request->transactionId ?? '';
                $client->campaign_id = $id_campaign ?? '';
                $client->cod_campaign = $campaign ?? '';
                $client->landing_type = substr($campaign, -1) ?? '';
                $client->cpa_customer = env('CPA_CUSTOMER') ?? '';
                $client->fullname = $request->customerName;
                $client->email = $request->customerEmail;
                $client->phone = $request->shippingAddress['phone'] ?? '';
                $client->state = $request->shippingAddress['state'];
                $client->country = $request->shippingAddress['country'] ?? '';
                $client->city = $request->shippingAddress['city'] ?? '';
                $client->zipcode = $request->shippingAddress['zipCode'] ?? '';
                $client->address = $request->shippingAddress['address'] ?? '';
                $client->bill_phone = $request->billingAddress['phone'] ?? '';
                $client->bill_state = $request->billingAddress['state'] ?? '';
                $client->bill_country = $request->billingAddress['country'] ?? '';
                $client->bill_city = $request->billingAddress['city'] ?? '';
                $client->bill_zipCode = $request->billingAddress['zipCode'] ?? '';
                $client->bill_address = $request->billingAddress['address'] ?? '';
                $client->payment_method = $request->paymentMethod;
                $client->items = $products ?? '';
                $client->qty = $qty;
                $client->discountCode = $request->discountCode;
                $client->discountAmount = $request->discountAmount;
                $client->total = $request->total;
                $client->paid = $request->paid;
                $client->utms = '';
                $client->privacy = $request->additionalFields[0]['value'] ?? '';
                $client->date = date('Y/m/d', $request->created);
                $client->note = '';
                $client->status = 0;
                $client->save();

                // $postData = [
                //     "pixel_code" => "C8PJ5SQO6DGM34P1FVG0",
                //     "type" => "track",
                //     "event" => "PlaceAnOrder",
                //     "event_id" => hash('sha256', $client->id) . '_' . $client->id_invoice,

                //     "context" => [
                //         "ad" => [
                //             "callback" => "$client->cod_campaign"
                //         ],
                //         "user" => [
                //             "external_id" => hash('sha256', $client->id),
                //             "phone_number" => hash('sha256', $client->phone),
                //             "email" => hash('sha256', $client->email),
                //         ]
                //     ]
                // ];

                // $curl = curl_init();

                // curl_setopt_array($curl, array(
                //     CURLOPT_URL => 'https://business-api.tiktok.com/open_api/v1.2/pixel/track/',
                //     CURLOPT_CUSTOMREQUEST => 'POST',
                //     CURLOPT_POSTFIELDS => json_encode($postData),
                //     CURLOPT_HTTPHEADER => array(
                //         'Access-Token: 874fc5a11f8dea5db480aea960d4d169b9b613a5',
                //         'Content-Type: application/json'
                //     ),
                // ));

                // $response = curl_exec($curl);

                // curl_close($curl);
                // Log::info($response);
                // echo $response;


                // Creo le righe degli ordini suddivise per ciascun prodotto 
                if (count($items) > 0) {
                    foreach ($items as $key => $item) {
                        $order_row = new OrdersRow;
                        $order_row->client_id = $client->id;
                        $order_row->row_id = $key + 1;
                        $order_row->product_id = $item['productId'];
                        $order_row->name = $item['name'];
                        $order_row->sku = $item['sku'];
                        $order_row->qty = $item['quantity'];
                        $order_row->total = $item['total'];
                        $order_row->type = $item['type'];
                        $order_row->shipping_required = $item['shippingRequired'];
                        $variations = $item['variation'];
                        $list_variations = [];
                        if (count($variations) > 0) {
                            foreach ($variations as $variation) {
                                $list_variations[] = $variation['name'];
                            }
                            $list_variations = implode(", ", $list_variations);
                            $order_row->variations = $list_variations;
                        }
                        $additions = $item['additions'];
                        $list_additions = [];
                        if (count($additions) > 0) {
                            foreach ($additions as $variation) {
                                $list_additions[] = $variation['name'];
                            }
                            $list_additions = implode(", ", $list_additions);
                            $order_row->additions = $list_additions;
                        }
                        $camp_ref = CampaignsReference::where('campaign_id', $client->campaign_id)->where('product_simvoly_id', $order_row->product_id)->pluck('id');
                        if (count($camp_ref) > 0) {
                            $order_row->camp_ref_id = $camp_ref[0];
                        }
                        $order_row->save();
                    }
                }

                // Controllo se esiste l'utente nella tabella degli utm
                $user = Utm::where('email', $client->email)->where('updated_at', '>', Carbon::now()->subMinutes(555))->get();

                // Se non esiste creo l'utente normalmente
                if (count($user) == 0) {
                    return response()->json([
                        'message' => 'Cliente aggiunto correttamente senza utms'
                    ]);
                } else {
                    // Se Ã¨ presente aggiungo nella tabella degli utms l'id del cliente
                    // Utm::where('email', $client->email)->where('updated_at', '>', Carbon::now()->subMinutes(555))->update(['client_id' => $client->id]);
                    $user_in_utm = Utm::where('email', $client->email)->where('updated_at', '>', Carbon::now()->subMinutes(555))->get();
                    if(count($user_in_utm)>0){
                        $user_in_utm = $user_in_utm[0]->id;
                        $client->utm_id = $user_in_utm;
                        $client->save();
                        Log::info('Utm aggiornato nella tabella');
                    }


                    $utm_user = Utm::where('client_id', $client->id)->get();
                    $utm = $utm_user[0]->utm ?? '';
                    if ($utm != '') {
                        parse_str($utm, $queryParams);
                        $utm_source = $queryParams['utm_source'] ?? '';

                        if ($utm_source != '') {
                            // Caso in cui sia di Taboola
                            if ($utm_source == 'Taboola') {
                                $id = $queryParams['click_id'] ?? $queryParams['clickid'];
                                $sub_id = $queryParams['sub_id'] ?? $queryParams['subid'];
                                Http::post('https://trc.taboola.com/actions-handler/log/3/s2s-action?click-id=' . $id . '&name=Dmu');
                                Log::info('Inviato a Taboola');
                            }

                            // Caso in cui sia di Outbrain
                            if ($utm_source == 'Outbrain') {
                                $id = $queryParams['click_id'] ?? $queryParams['clickid'];
                                $sub_id = $queryParams['sub_id'] ?? $queryParams['subid'];
                                Http::post('https://tr.outbrain.com/pixel?ob_click_id=' . $id . '&name=Dmu');
                                Log::info('Inviato a Outbrain');
                            }
                        }
                    }


                    return response()->json([
                        'message' => 'Cliente aggiornato con CC'
                    ]);
                }
            }
        }
    }
}
