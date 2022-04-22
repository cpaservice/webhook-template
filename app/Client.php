<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = ['cod_campaign'];
    public function campaign()
    {
        return $this->hasOne('App\Campaign', 'id', 'campaign_id');
    }
    public function ordersRow()
    {
        return $this->belongsTo('App\OrdersRow');
    }
}
