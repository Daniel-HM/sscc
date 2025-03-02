<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $omschrijving
 * @property-read \App\Models\Sscc|null $Sscc
 * @method static \Database\Factories\OrdertypesFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ordertypes newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ordertypes newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ordertypes query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ordertypes whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ordertypes whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ordertypes whereOmschrijving($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Ordertypes whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Ordertypes extends Model
{
    /** @use HasFactory<\Database\Factories\OrdertypesFactory> */
    use HasFactory;

    protected $table = 'ordertypes';

    protected $guarded = [];

    /**
     * Get the Sscc that owns the Ordertypes
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Sscc(): BelongsTo
    {
        return $this->belongsTo(Sscc::class);
    }
}
