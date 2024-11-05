<?php

namespace App\Infrastructures\TransactionHistory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'customer',
        'user',
        'type',
        'description',
        'date',
    ];
}
