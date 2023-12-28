<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;
    
    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function contract(){
        return $this->hasMany(Contract::class);
    }
    public function room()
    {
        return $this->hasMany(rooms::class);
    }
}
