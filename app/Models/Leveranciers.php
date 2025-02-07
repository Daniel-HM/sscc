<?php
namespace App\Models;

use Database\Factories\LeveranciersFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
