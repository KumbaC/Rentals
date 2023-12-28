<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function property(){
        return $this->belongsTo(Property::class);
    }

    public function room(){
        return $this->belongsTo(rooms::class);
    }

    public function TypeCurrency(){
        return $this->hasMany(TypeCurrency::class);
    }
}
