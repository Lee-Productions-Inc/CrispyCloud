<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    protected $table = 'admin_users';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'full_name',
        'email',
        'password_hash',
        'role',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function getAuthPasswordName()
{
    return 'password_hash';
}

public function getAuthPassword()
{
    return $this->password_hash;
}
}