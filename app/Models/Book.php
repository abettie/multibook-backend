<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends BaseModel
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    protected $fillable = ['name', 'thumbnail', 'user_id'];

    /**
     * Get all of the items for the Book
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get all of the kinds for the Book
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function kinds(): HasMany
    {
        return $this->hasMany(Kind::class);
    }

    public function getThumbnailAttribute($value)
    {
        return config('app.img_endpoint') . '/thumbnails/' . ($value ?: 'no-image.png');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
