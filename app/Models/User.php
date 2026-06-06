<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',          
        'is_approved',   
        'category_id',   
    ];
    
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function ticketsAsClient()
    {
        return $this->hasMany(Ticket::class, 'client_id');
    }

    public function ticketsAsStaff()
    {
        return $this->hasMany(Ticket::class, 'staff_id');
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            $user->tickets->each->delete();
        });
    }

    /**
     * Отримує всі тікети, призначені на цього працівника.
     */
    public function tickets()
    {
        // Обов'язково вказуємо 'staff_id', бо інакше Laravel за замовчуванням 
        // буде шукати колонку 'user_id' в таблиці tickets і видасть помилку.
        return $this->hasMany(Ticket::class, 'staff_id');
    }
}
