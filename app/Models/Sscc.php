<?php
namespace App\Models;

use App\Models\Artikels;
use App\Models\Ordertypes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $sscc
 * @property int $aantal_collo
 * @property int $aantal_ce
 * @property int $artikel_id
 * @property int $ordertype_id
 * @property int $pakbon_id
 * @property-read Artikels|null $Artikels
 * @property-read Ordertypes|null $Ordertypes
 * @property-read \App\Models\Pakbonnen|null $Pakbonnen
 * @property-read Artikels|null $artikel
 * @method static \Database\Factories\SsccFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereAantalCe($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereAantalCollo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereArtikelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereOrdertypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc wherePakbonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereSscc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sscc whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
