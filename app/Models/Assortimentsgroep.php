<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Assortimentsgroep extends Model
{
    /** @use HasFactory<\Database\Factories\AssortimentsgroepFactory> */
    use HasFactory;

    protected $table = 'assortimentsgroep';

    protected $guarded = [];

    /**
     * Get the Artikels that owns the Assortimentsgroep
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Artikels(): HasOne
    {
        return $this->hasOne(Artikels::class, 'id');
    }
}
