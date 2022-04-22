<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CampaignsReference extends Model
{
    public function ordersRows()
    {
        return $this->hasMany('App\OrdersRow');
    }
    public function reference()
    {
        return $this->hasOne('App\Reference', 'id', 'reference_id');
    }
    public function campaign()
    {
        return $this->hasOne('App\Campaign', 'id', 'campaign_id');
    }
}
