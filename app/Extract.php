<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Extract extends Model
{
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_user', 'id_plan', 'id_client', 'value', 'type', 'percent', 'points', 'side', 'created_at'
    ];
}
