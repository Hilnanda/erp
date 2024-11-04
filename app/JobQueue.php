<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class JobQueue extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'next_schedule_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class);
    }

    /**
     * Retrieves ids in array
     */
    public function getIdsAttribute($value)
    {
        return $value ? explode(',', $value) : $value;
    }
}
