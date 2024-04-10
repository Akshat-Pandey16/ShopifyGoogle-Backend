<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthToken extends Model
{
    use HasFactory;
    protected $table = 'auth_tokens';
    protected $primaryKey = 'tokens_id';
    protected $fillable = ['user_id', 'google_access_token', 'google_refresh_token', 'shopify_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
