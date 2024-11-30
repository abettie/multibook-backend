<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    } 

    // デフォルトでcreated_at, updated_atは取得しない。
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
