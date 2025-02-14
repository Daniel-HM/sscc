<?php
namespace App\Models;

use App\Models\Artikels;
use App\Models\Ordertypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Artikels(): BelongsTo
    {
        return $this->belongsTo(Artikels::class);
    }

    /**
     * Get all of the Pakbonnen for the Sscc
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Pakbonnen(): BelongsTo
    {
        return $this->belongsTo(Pakbonnen::class, 'pakbon_id');
    }

    // Define the relationship to the Artikel model
    public function artikel()
    {
        return $this->belongsTo(Artikels::class);
    }

    public function Ordertypes(): BelongsTo
    {
        return $this->belongsTo(Ordertypes::class, 'ordertype_id');
    }

}
