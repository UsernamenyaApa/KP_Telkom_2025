<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramGroup extends Model
{
    protected $fillable = ['chat_id', 'name'];
}
