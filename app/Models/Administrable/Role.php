<?php

namespace App\Models\Administrable;

use Illuminate\Database\Eloquent\Model;

class Role extends Model{

    public $table = "roles";

    protected $fillable = [
        'id',
        'slug',
        'name',
        'description'
    ];
    
    public function permissions(){
        return $this->belongsToMany(Permission::class, 'roles_permissions', 'role_id', 'permission_id');
    }

}