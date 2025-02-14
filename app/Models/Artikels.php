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
}
