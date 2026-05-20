<?php
declare(strict_types=1);

namespace Vancil\FlintAuth\Models;

use Flint\Model;

class RefreshToken extends Model
{
    protected string $table    = 'refresh_tokens';
    protected array  $fillable = ['user_id', 'jti', 'expires_at'];
    protected bool   $timestamps = true;
}
