<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{

    protected $fillable = ['name'];

    // Enable timestamps (created_at, updated_at) — true by default
    public $timestamps = true;


}
