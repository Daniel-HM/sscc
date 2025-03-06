<?php
namespace App\Models;

use Database\Factories\PakbonnenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $naam
 * @property int $isVerwerkt
 * @property int $isConverted
 * @property int $movedToFolder
 * @property string $pakbonDatum
 * @property-read \App\Models\Artikels|null $Artikels
 * @property-read \App\Models\Sscc|null $Sscc
 * @method static \Database\Factories\PakbonnenFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen whereIsConverted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen whereIsVerwerkt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen whereMovedToFolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen whereNaam($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen wherePakbonDatum($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pakbonnen whereUpdatedAt($value)
 * @mixin \Eloquent
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


    public function Sscc(): BelongsToMany
    {
        return $this->BelongsToMany(Sscc::class);
    }

    public function Artikels(): BelongsTo
    {
        return $this->belongsTo(Artikels::class);
    }
}
