<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $omschrijving
 * @property-read \App\Models\Artikels|null $Artikels
 * @method static \Database\Factories\KassagroepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kassagroep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kassagroep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kassagroep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kassagroep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kassagroep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kassagroep whereOmschrijving($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Kassagroep whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
