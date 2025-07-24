<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffVerification extends Model
{
    use HasFactory;

    protected $fillable = ['staff_id', 'code', 'expires_at'];

    protected $dates = ['expires_at'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
