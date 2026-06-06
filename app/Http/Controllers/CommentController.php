<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Comment;

class CommentController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        // Перевіряємо, чи текст не порожній
        $request->validate([
            'content' => 'required|string',
        ]);

        // Створюємо новий коментар у базі
        Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        // Повертаємо користувача назад на сторінку тікета
        return back()->with('success', 'Коментар додано!');
    }
}