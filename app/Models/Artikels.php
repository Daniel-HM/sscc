<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
    public function Sscc(): HasMany
    {
        return $this->hasMany(Sscc::class);
    }

    /**
     * Get the Assortimentsgroep associated with the Artikels
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Assortimentsgroep(): BelongsTo
    {
        return $this->belongsTo(Assortimentsgroep::class, 'assortimentsgroep_id');
    }

    /**
     * Get the Kassagroep associated with the Artikels
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Kassagroep(): BelongsTo
    {
        return $this->belongsTo(Kassagroep::class, 'kassagroep_id');
    }

    /**
     * Get the Leveranciers associated with the Artikels
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function Leveranciers(): HasOne
    {
        return $this->hasOne(Leveranciers::class, 'id', 'leverancier_id');
    }
}
