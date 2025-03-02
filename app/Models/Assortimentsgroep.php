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
 * @method static \Database\Factories\AssortimentsgroepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assortimentsgroep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assortimentsgroep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assortimentsgroep query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assortimentsgroep whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assortimentsgroep whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assortimentsgroep whereOmschrijving($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Assortimentsgroep whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
