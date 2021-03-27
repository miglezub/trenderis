<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    use HasFactory;
    protected $fillable = [
        'original_text', 
        'use_word2vec',
        'use_idf', 
        'trained_word2vec',
        'language_id'
    ]; 

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }
}
