<?php
namespace App\Models;

use Database\Factories\LeveranciersFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $naam
 * @property string|null $telefoon
 * @property string|null $email
 * @property int|null $franco
 * @property string|null $adres_straat
 * @property string|null $adres_postcode
 * @property string|null $adres_land
 * @property string|null $adres_plaatsnaam
 * @property-read \App\Models\Artikels|null $Artikels
 * @method static \Database\Factories\LeveranciersFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereAdresLand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereAdresPlaatsnaam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereAdresPostcode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereAdresStraat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereFranco($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereNaam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereTelefoon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Leveranciers whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Leveranciers extends Model
{
    /** @use HasFactory<LeveranciersFactory> */
    use HasFactory;

    protected $table = 'leveranciers';

    protected $guarded = [];

    /**
     * Get the Artikels that owns the Leveranciers
     *
     * @return BelongsTo
     */
    public function Artikels(): BelongsTo
    {
        return $this->belongsTo(Artikels::class);
    }
}
