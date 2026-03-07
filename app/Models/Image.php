<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends BaseModel
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    protected $fillable = ['item_id', 'file_name'];

    /**
     * Get the item that owns the Image
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getFileNameAttribute($value)
    {
        $file = $value ?: 'no-image.png';
        if (app()->environment('local')) {
            return '/img/images/' . $file;
        }
        return config('app.img_endpoint') . '/images/' . $file;
    }
}
