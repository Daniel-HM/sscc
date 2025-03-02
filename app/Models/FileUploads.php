<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $filename
 * @property int $convertedToCsv
 * @property int $processedIntoDb
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads whereConvertedToCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads whereProcessedIntoDb($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FileUploads whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FileUploads extends Model
{
    protected $table = 'file_uploads';

    protected $guarded = [];
}
