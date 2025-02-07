<?php
namespace App\Models;

use App\Models\Artikels;
use App\Models\Ordertypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sscc extends Model
{
    /** @use HasFactory<\Database\Factories\SsccFactory> */
    use HasFactory;

    protected $guarded = [];

    // NO PLURAL FFS
    protected $table = 'sscc';

    /**
     * Get all of the Artikels for the Sscc
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Artikels(): HasMany
    {
        return $this->hasMany(Artikels::class, 'id', 'artikel_id');
    }

    /**
     * Get all of the Pakbonnen for the Sscc
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Pakbonnen(): HasMany
    {
        return $this->hasMany(Pakbonnen::class);
    }

    // Define the relationship to the Artikel model
    public function artikel()
    {
        return $this->belongsTo(Artikels::class, 'artikel_id');
    }

    public function ordertypes()
    {
        return $this->belongsTo(Ordertypes::class, 'ordertype_id');
    }

}
