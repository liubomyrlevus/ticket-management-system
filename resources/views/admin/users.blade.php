<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

<div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="border-b py-2 px-4">Name</th>
                            <th class="border-b py-2 px-4">Email</th>
                            <th class="border-b py-2 px-4">Role</th>
                            <th class="border-b py-2 px-4">Approved</th>
                            <th class="border-b py-2 px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50 border-b">
                            <td class="py-2 px-4">{{ $user->name }}</td>
                            <td class="py-2 px-4">{{ $user->email }}</td>
                            
                            <td class="py-2 px-4">
                                <form action="{{ route('admin.update', $user) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <select name="role" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                                        <option value="client" {{ $user->role == 'client' ? 'selected' : '' }}>Client</option>
                                        <option value="staff" {{ $user->role == 'staff' ? 'selected' : '' }}>Staff</option>
                                        <option value="admin" {{ $user->role == 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                </form>
                            </td>

                            <td class="py-2 px-4">
                                <form action="{{ route('admin.update', $user) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="checkbox" name="is_approved" value="1" onchange="this.form.submit()" {{ $user->is_approved ? 'checked' : '' }}>
                                </form>
                            </td>

                            <td class="py-2 px-4">
                                <form action="{{ route('admin.destroy', $user) }}" method="POST" onsubmit="return confirm('Видалити користувача?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-4 rounded shadow-md transition duration-200">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>