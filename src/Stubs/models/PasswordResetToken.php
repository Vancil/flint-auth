<?php
declare(strict_types=1);

namespace App\Models;

use Flint\Model;

class PasswordResetToken extends Model
{
    protected string $table = 'password_reset_tokens';

    protected array $fillable = ['email', 'token', 'created_at'];

    public $timestamps = false;
}
