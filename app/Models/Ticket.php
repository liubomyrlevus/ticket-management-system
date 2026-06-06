<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
use HasFactory, SoftDeletes;

    // Дозволяємо масове заповнення цих полів
    protected $fillable = [
        'title', 
        'description', 
        'status', 
        'client_id', 
        'staff_id', 
        'category_id', 
        'priority_id'
    ];

    // Зв'язок з клієнтом (хто створив)
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
    }

    // Зв'язок з виконавцем (кому призначив Admin)
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id')->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    // Додай цей метод всередину класу Ticket
    public function comments()
    {
        // Отримуємо коментарі від найстарішого до найновішого
        return $this->hasMany(Comment::class)->orderBy('created_at', 'asc');
    }

    protected static function booted()
    {
        static::deleting(function ($ticket) {
            // Тікет просто ховає всі свої коментарі
            $ticket->comments()->delete();
        });
    }
}
