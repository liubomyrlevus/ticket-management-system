<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Список всіх юзерів для Адміна
    public function users()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }

    // Зміна ролі та статусу
    public function updateUser(Request $request, User $user)
    {
        // Оновлюємо тільки те, що прийшло в запиті
        $data = [];
        if ($request->has('role')) $data['role'] = $request->role;
        
        // Якщо чекбокс, то він або приходить (1), або ні (null)
        $data['is_approved'] = $request->has('is_approved') ? 1 : 0;

        $user->update($data);
        return back()->with('success', 'Оновлено!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Ви не можете видалити свій власний акаунт!');
        }
        $user->delete();
        return back()->with('success', 'Користувача успішно видалено.');
    }
}