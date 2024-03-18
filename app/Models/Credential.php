<?php

namespace App\Models;

use App\Models\SystemConfiguration\AccessManagement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Credential extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'credentials';
    protected $fillable = [
        'employee_id',
        'password',
        'user_access_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function userAccess()
    {
        return $this->belongsTo(UserAccess::class, 'user_access_id');
    }
}
