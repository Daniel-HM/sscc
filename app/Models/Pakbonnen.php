<?php
namespace App\Models;

use Database\Factories\PakbonnenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static firstOrCreate(array $array)
 */
class Pakbonnen extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    // DO NOT PLURALIZE DANGIT
    protected $table = 'pakbonnen';

    /** @use HasFactory<PakbonnenFactory> */
    use HasFactory;

    /**
     * Get the Sscc that owns the Pakbonnen
     *
     * @return BelongsTo
     */
    public function Sscc(): BelongsTo
    {
        return $this->belongsTo(Sscc::class);
    }
}
