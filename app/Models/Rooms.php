<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rooms extends Model
{
    use HasFactory;
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function property(){
        return $this->belongsTo(Property::class);
    }

    public function contract(){
        return $this->hasMany(Contract::class);
    }

}
