<?php

namespace App\Models\Administrable;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model{

    public $table = "roles_permissions";

    protected $fillable = [
        'id',
        'role_id',
        'permission_id',
        'status'
    ];
    
    public function role() {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    public function permission() {
        return $this->belongsTo(Permission::class, 'permission_id');
    }

}