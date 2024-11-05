<?php

namespace App\Infrastructures\Feedback\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'feedbacks';

    protected $fillable = [
        'identifier',
        'type',
        'status',
        'content',
        'updated_at',
    ];
}
