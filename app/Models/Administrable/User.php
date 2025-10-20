<?php

namespace App\Models\Administrable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable{

    use HasFactory, Notifiable;

    public $table = "users";

    protected $fillable = [
        'id',
        'username',
        'name',
        'email',
        'phone',
        'password',
        'password_decrypted',
        'role_id',
        'status',
    ];

    public function role() {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
}