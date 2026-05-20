<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = ['destinataire_nom', 'telephone', 'message', 'type', 'statut', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];
}
