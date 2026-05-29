<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SonarConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'connection_name',
        'sonar_url',
        'token',
        'active',
        'last_tested_at',
        'last_test_status',
        'last_test_result',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'active' => 'boolean',
            'last_test_status' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }
}
