<?php

namespace App\Infrastructures\Visit\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'identifier';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
      'identifier',
      'user',
      'visited_at',
      'phone_area_code',
      'phone_local_code',
      'phone_subscriber_number',
      'postal_code_first',
      'postal_code_second',
      'prefecture',
      'city',
      'street',
      'building',
      'note',
      'has_graveyard',
      'result',
    ];
}
