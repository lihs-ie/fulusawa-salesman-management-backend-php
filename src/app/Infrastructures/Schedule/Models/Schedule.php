<?php

namespace App\Infrastructures\Schedule\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'user',
        'customer',
        'title',
        'description',
        'start',
        'end',
        'status',
        'repeat',
    ];
}
