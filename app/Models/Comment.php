<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['ticket_id', 'user_id', 'content'];

    // Зв'язок: коментар належить користувачу (автору)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Зв'язок: коментар належить тікету
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
