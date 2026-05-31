<?php
declare(strict_types=1);

namespace App\Models;

use Flint\Model;

class EmailVerification extends Model
{
    protected string $table = 'email_verifications';

    protected array $fillable = ['email', 'token', 'created_at'];

    public $timestamps = false;
}
