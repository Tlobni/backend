<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturedUsers extends Model {

    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
        'user_id',
        'package_id',
        'user_purchased_package_id',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function scopeOnlyActive($query) {
        return $query->whereDate('start_date', '<=', date('Y-m-d'))->where(function ($q) {
            $q->whereDate('end_date', '>=', date('Y-m-d'))->orWhereNull('end_date');
        });
    }
} 