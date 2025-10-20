<?php

namespace App\Models\Administrable;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model{

    public $table = "permissions";

    protected $fillable = [
        'id',
        'slug',
        'name',
        'description'
    ];
    
    public function roles(){
        return $this->hasMany(Role::class, 'role_id');
    }

}