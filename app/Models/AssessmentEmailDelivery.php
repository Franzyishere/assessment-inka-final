<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentEmailDelivery extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'accepted' => 'Diterima layanan email',
            'failed' => 'Gagal dikirim',
            'cancelled' => 'Dibatalkan',
            default => 'Dalam antrean',
        };
    }
}
