<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
