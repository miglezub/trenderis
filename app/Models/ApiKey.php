<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'name',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function texts()
    {
        return $this->hasMany(Text::class, 'api_key_id');
    }
}
