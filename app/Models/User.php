<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'full_name',
        'email',
        'phone_number',
        'password_hash',
        'terms_accepted_at',
        'terms_version',
        'privacy_notice_version',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'user_id', 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id', 'user_id');
    }

    #[Override]
    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}