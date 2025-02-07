<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kassagroep extends Model
{
    /** @use HasFactory<\Database\Factories\KassagroepFactory> */
    use HasFactory;

    protected $table = 'kassagroep';

    protected $guarded = [];

/**
 * Get the Artikels that owns the Kassagroep
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 * @var  \Illuminate\Database\Eloquent\Collection\Artikels
 */
    public function Artikels(): HasOne
    {
        return $this->hasOne(Artikels::class);
    }
}
