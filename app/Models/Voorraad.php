<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voorraad extends Model
{
    // PLURAL BAD
    protected $table = 'voorraad';

    protected $guarded = [];

    public function Artikels(): BelongsTo
    {
        return $this->belongsTo(Artikels::class);
    }
}
