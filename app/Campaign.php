<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['cod_campaign'];

    public function clients()
    {
        return $this->belongsTo('App\Client');
    }
}
