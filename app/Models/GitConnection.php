<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GitConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'connection_name',
        'provider_type',
        'base_url',
        'auth_type',
        'username',
        'token',
        'ssh_private_key',
        'active',
        'last_tested_at',
        'last_test_status',
        'last_test_result',
    ];

    protected $hidden = [
        'token',
        'ssh_private_key',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'ssh_private_key' => 'encrypted',
            'active' => 'boolean',
            'last_test_status' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }
}
