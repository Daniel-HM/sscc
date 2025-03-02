<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $ean
 * @property string $artikelnummer_it
 * @property string $artikelnummer_leverancier
 * @property string $omschrijving
 * @property int $leverancier_id
 * @property int $assortimentsgroep_id
 * @property int $kassagroep_id
 * @property string $verkoopprijs
 * @property-read \App\Models\Assortimentsgroep|null $Assortimentsgroep
 * @property-read \App\Models\Kassagroep|null $Kassagroep
 * @property-read \App\Models\Leveranciers|null $Leveranciers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sscc> $Sscc
 * @property-read int|null $sscc_count
 * @method static \Database\Factories\ArtikelsFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereArtikelnummerIt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereArtikelnummerLeverancier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereAssortimentsgroepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereEan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereKassagroepId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereLeverancierId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereOmschrijving($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Artikels whereVerkoopprijs($value)
 * @mixin \Eloquent
 */
class Artikels extends Model
{
    /** @use HasFactory<\Database\Factories\ArtikelsFactory> */
    use HasFactory;

    // PLURAL BAD
    protected $table = 'artikels';

    protected $guarded = [];


    /**
     * Get the Sscc that owns the Artikels
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Sscc(): hasMany
    {
        return $this->hasMany(Sscc::class, 'artikel_id', 'id');
    }

    /**
     * Get the Assortimentsgroep associated with the Artikels
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Assortimentsgroep(): BelongsTo
    {
        return $this->belongsTo(Assortimentsgroep::class);
    }

    /**
     * Get the Kassagroep associated with the Artikels
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Kassagroep(): BelongsTo
    {
        return $this->belongsTo(Kassagroep::class);
    }

    /**
     * Get the Leverancier associated with the Artikel
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Leveranciers(): BelongsTo
    {
        return $this->belongsTo(Leveranciers::class, 'leverancier_id', 'id');
    }

    public function Voorraad(): HasOne
    {
        return $this->hasone(Voorraad::class, 'artikel_id', 'id');
    }
}
