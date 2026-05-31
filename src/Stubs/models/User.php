<?php
declare(strict_types=1);

namespace App\Models;

use Flint\Model;

class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'remember_token',
        'email_verified_at',
    ];

    protected array $hidden = ['password', 'remember_token'];

    public function isVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
}
