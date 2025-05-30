<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserPixel extends Model
{

    public $table = "user_pixel";

    protected $fillable = [
        'user_id',
        'type',
        'head',
        'body'
    ];
}
