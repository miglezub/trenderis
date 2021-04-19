<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TextAnalysis extends Model
{
    use HasFactory;
    protected $table = 'text_analysis';
    protected $fillable = [
        'use_word2vec',
        'use_idf',
        'lemmatized_text',
        'results',
        'duration',
        'top_results'
    ]; 

    public function text()
    {
        return $this->belongsTo(Text::class, 'text_id');
    }
}
