<style>
    *{
        font-family: sans-serif;
    }
    table{
        border: 1px solid black;
    }
    td,th{
        padding: 10px 10px;
    }
    td{
        border-bottom: 1px solid black;
    }
</style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body>
    
<h2>Hai ricevuto un nuovo ordine:</h2>
<br>
<p><b>Ordine nr:</b> {{$lead->id_invoice}}</p>
<p><b>Del:</b> {{date_format($lead->created_at, 'd/m/Y H:i')}}</p>
<p><b>Campagna:</b> {{$lead->campaign->title}}</p>
<p><b>Nome:</b> {{$lead->fullname}}</p>
<p><b>Email:</b> {{$lead->email}}</p>
<p><b>Telefono:</b> {{$lead->phone}}</p>
<p><b>Via/strada:</b>  {{$lead->address}}
<p><b>Città:</b>  {{$lead->city}}
<p><b>CAP:</b>  {{$lead->zipcode}}
<p><b>Provincia:</b>  {{$lead->state}}
<br>
<table>
    <th>Prodotto</th>
    <th>Quantit&aacute;</th>
    <th>Totale Parziale</th>

    <tbody>
        @foreach ($order as $single)
        @if ($single->campaignsReference->reference->type == 'bundle')
            @php
                $items = DB::table('bundle_reference')->select('name')->where('reference_id', $single->campaignsReference->reference->id )->get();
            @endphp
            @foreach ($items as $item)
            <tr>
                <td>[Bundle] {{$item->name}}</td>
                <td>{{$single->qty}}</td>
                <td></td>
            </tr>
            @endforeach
            <tr>
                <td style="text-align: right">Totale Bundle</td>
                <td></td>
                <td>&euro; {{$single->total}}</td>
            </tr>
        @else
            <tr>
                <td>{{$single->campaignsReference->reference->name_art}}</td>
                <td>{{$single->qty}}</td>
                <td>&euro; {{$single->total}}</td>
            </tr>
        @endif
        @endforeach
        @if ($lead->discountCode != '')
            <tr>
                <td><b>Buono sconto</b></td>
                <td>{{$lead->discountCode}}</td>
                <td>&euro; -{{$lead->discountAmount}}</td>
            </tr>
        @endif
        <tr>
            <td><b>Metodo di pagamento:</b></td>
                <td>{{$lead->payment_method}}</td>
                <td></td>
        </tr>
        <tr>
            <td><b>Totale prodotti:</b></td>
            <td></td>
            <td><b>&euro; {{$lead->total}}</b></td>
        </tr>
        @if($lead->payment_method == 'cod')
            @php
            $total_n = floatval($lead->total);
            $new_total = $total_n + 2.90;
            @endphp
            <tr>
                <td><p><b>Contrassegno</b></p></td>
                <td></td>
                <td>&euro; 2.90</td>
            </tr>
            <tr>
                <td><b>Totale da pagare:</td> 
                <td></td>
                <td><b>&euro; {{$new_total}}</b></td>
            </tr>
        @else
            <tr>
                <td><b>Totale da pagare:</td> 
                <td></td>
                <td><b>&euro; {{$lead->total}}</b></td>
            </tr>
        @endif
    </tbody>
</table>

<p><b>Stato pagamento:</b> {{($lead->paid ? 'pagato' : 'non pagato') }}</p>
    
</body>
</html>


