<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\Category;
use App\Models\Priority;
use App\Models\User;
use App\Models\Comment;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource with Analytics.
     */
public function index(Request $request)
    {
        $user = auth()->user();
        
        // Починаємо формувати запит з підвантаженням зв'язків
        $query = Ticket::with(['category', 'priority', 'client', 'staff']);

        // 1. Обмеження видимості по ролях
        if ($user->role === 'client') {
            // Клієнт бачить лише свої тікети
            $query->where('client_id', $user->id);
        } elseif ($user->role === 'staff') {
            // Працівник бачить тікети ТІЛЬКИ свого відділу (Unassigned + ті, що на колегах)
            // АБО тікети, які були безпосередньо призначені на нього
            $query->where(function ($q) use ($user) {
                $q->where('category_id', $user->category_id)
                  ->orWhere('staff_id', $user->id);
            });
        }
        // Адміни ('admin') проходять далі без обмежень і бачать все

        // 2. Фільтри з форми дат
        $query->when($request->filled('date_from'), function ($q) use ($request) {
            $q->whereDate('created_at', '>=', $request->date_from);
        })->when($request->filled('date_to'), function ($q) use ($request) {
            $q->whereDate('created_at', '<=', $request->date_to);
        });

        // 3. НОВІ ФІЛЬТРИ З ЗАГОЛОВКІВ (Excel-стайл)
        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('category_id', $request->category);
        });
        $query->when($request->filled('priority'), function ($q) use ($request) {
            $q->where('priority_id', $request->priority);
        });
        $query->when($request->filled('status'), function ($q) use ($request) {
            $q->where('status', $request->status);
        });
        
        // Фільтри по людях (тільки для Адміна/Стаффу)
        if ($user->role !== 'client') {
            $query->when($request->filled('client'), function ($q) use ($request) {
                $q->where('client_id', $request->client);
            });
            $query->when($request->filled('staff'), function ($q) use ($request) {
                $q->where('staff_id', $request->staff);
            });
        }

        // 4. Отримуємо список тікетів для таблиці
        $tickets = (clone $query)->paginate(10)->withQueryString();

        // ==========================================
        // 5. БЛОК АНАЛІТИКИ (на основі відфільтрованих даних)
        // ==========================================
        $totalTickets = (clone $query)->count();
        $pendingTasks = (clone $query)->whereIn('status', ['New', 'In Progress'])->count();
        $resolvedTasks = (clone $query)->whereIn('status', ['Resolved', 'Closed'])->count();

        // Розподіл по категоріях
        $categorySummary = (clone $query)
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get();

        // Аналіз продуктивності
        $performance = (clone $query)
            ->whereIn('status', ['Resolved', 'Closed', 'Rejected'])
            ->selectRaw('
                AVG((julianday(updated_at) - julianday(created_at)) * 86400) as avg_time,
                MIN((julianday(updated_at) - julianday(created_at)) * 86400) as min_time,
                MAX((julianday(updated_at) - julianday(created_at)) * 86400) as max_time
            ')->first();

        // ==========================================
        // 6. ДОДАЄМО ДАНІ ДЛЯ ВИПАДАЮЧИХ МЕНЮ
        // ==========================================
        $categories = Category::all();
        $priorities = Priority::all();
        $clients = User::where('role', 'client')->get();
        $staffMembers = User::whereIn('role', ['staff', 'admin'])->get();
        $statuses = ['New', 'In Progress', 'Resolved', 'Closed', 'Rejected'];

        // 7. Повертаємо все у в'юху
        return view('tickets.index', compact(
            'tickets', 'totalTickets', 'pendingTasks', 'resolvedTasks', 'categorySummary', 'performance',
            'categories', 'priorities', 'clients', 'staffMembers', 'statuses'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::all();
        $priorities = Priority::all();

        return view('tickets.create', compact('categories', 'priorities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Перевірка: чи схвалений користувач адміністратором?
        if (!auth()->user()->is_approved) {
            return back()->with('error', 'Your account is not approved yet by the admin.');
        }

        // 2. Валідація даних
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'priority_id' => 'required|exists:priorities,id',
        ]);

        // 3. Додаємо ID залогіненого користувача (client_id)
        $validated['client_id'] = auth()->id();
        
        // 4. Статус за замовчуванням 'New'
        $validated['status'] = 'New';

        // --- СУПЕР-РОЗУМНЕ АВТОПРИЗНАЧЕННЯ (Load Balancing) ---
        $staffMembers = User::where('role', 'staff')
            ->where('category_id', $validated['category_id'])
            ->withCount(['tickets' => function ($query) {
                $query->where('status', '!=', 'Closed'); 
            }])
            ->get();

        if ($staffMembers->isEmpty()) {
            $validated['staff_id'] = null;
        } elseif ($staffMembers->count() === 1) {
            $validated['staff_id'] = $staffMembers->first()->id;
        } else {
            $leastBusyStaff = $staffMembers->sortBy('tickets_count')->first();
            $validated['staff_id'] = $leastBusyStaff->id;
        }

        // 5. Зберігаємо тікет у базу
        Ticket::create($validated);

        return redirect()->route('tickets.index')->with('success', 'Ticket created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        // Захист
        if (auth()->user()->role === 'client' && $ticket->client_id !== auth()->id()) {
            abort(403, 'You do not have access to this ticket.');
        }

        $ticket->load(['client', 'staff', 'comments.user']);
        
        $staffMembers = collect(); 
        
        if (auth()->user()->role !== 'client') {
            // Беремо працівників потрібного відділу АБО всіх адмінів
            $staffMembers = User::where(function ($query) use ($ticket) {
                $query->where('role', 'staff')
                      ->where('category_id', $ticket->category_id);
            })->orWhere('role', 'admin')->get();
        }

        return view('tickets.show', compact('ticket', 'staffMembers'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        // БАЗОВИЙ ЗАХИСТ: Клієнт не може редагувати чужі тікети
        if (auth()->user()->role === 'client' && $ticket->client_id !== auth()->id()) {
            abort(403, 'You do not have access to update this ticket.');
        }

        $data = [];

        // 1. ШВИДКІ ДІЇ ВИКОНАВЦЯ (Resolve, Reject, Release)
        // Слухає форму з кнопками стаффу
        if ($request->has('quick_action')) {
            $request->validate(['action_comment' => 'required|string']);

            $action = $request->quick_action;
            $commentContent = '';

            if ($action === 'release') {
                $data['staff_id'] = null;
                $data['status'] = 'New'; // Автоматично скидаємо статус на New
                $commentContent = 'Released the ticket. Reason: ' . $request->action_comment;
            } elseif (in_array($action, ['Resolved', 'Rejected'])) {
                $data['status'] = $action;
                $commentContent = "Changed status to {$action}. Reason: " . $request->action_comment;
            }

            \App\Models\Comment::create([
                'ticket_id' => $ticket->id,
                'user_id' => auth()->id(),
                'content' => $commentContent,
            ]);

            $ticket->update($data);
            
            // Завершили роботу -> кидаємо на загальний список
            return redirect()->route('tickets.index')->with('success', 'Ticket processed successfully!');
        }

        // 2. ДІЇ КЛІЄНТА (Відповідь на Resolved або Rejected з обов'язковим коментарем)
        // Слухає нову жовту панель клієнта
        if ($request->has('client_action')) {
            $request->validate([
                'client_comment' => 'required|string',
                'client_action' => 'required|in:Closed,In Progress'
            ]);

            if (in_array($ticket->status, ['Resolved', 'Rejected'])) {
                $data['status'] = $request->client_action;
                
                $actionText = $request->client_action === 'Closed' ? 'Accepted and closed' : 'Rejected and reopened';
                
                \App\Models\Comment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => auth()->id(),
                    'content' => "Client: {$actionText}. Reason: " . $request->client_comment,
                ]);

                $ticket->update($data);
                
                // Клієнт прийняв рішення -> кидаємо на загальний список
                return redirect()->route('tickets.index')->with('success', 'Ticket processed successfully!');
            }
        }

        // 3. ЗМІНА СТАТУСУ (Через випадайку Адміна)
        if ($request->has('status') && auth()->user()->role !== 'client') {
            $data['status'] = $request->status;
            // Авто-захоплення тікета при зміні статусу
            if ($request->status != $ticket->status) {
                $data['staff_id'] = auth()->id();
            }
        }

        // 4. ЗМІНА ВИКОНАВЦЯ (Випадайка адміна або кнопка Claim)
        if ($request->has('staff_id')) {
            $data['staff_id'] = $request->staff_id;
            
            // Якщо хтось натиснув Claim, відразу ставимо статус In Progress
            if (is_null($ticket->staff_id) && $request->staff_id == auth()->id()) {
                $data['status'] = 'In Progress';
            }
        }

        // Зберігаємо зміни з блоків 3 та 4
        if (!empty($data)) {
            $ticket->update($data);
        }

        // Якщо це була просто зміна через випадайку (блоки 3 і 4), залишаємо на сторінці
        return back()->with('success', 'Ticket updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Update only the status of the ticket.
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:New,In Progress,Resolved,Closed,Rejected',
        ]);

        $data = ['status' => $request->status];

        // Будь-яка зміна статусу адміном або стаффом автоматично закріплює цей тікет за ним
        if (auth()->user()->role === 'staff' || auth()->user()->role === 'admin') {
            $data['staff_id'] = auth()->id();
        }

        $ticket->update($data);

        return back()->with('success', 'Ticket status updated successfully!');
    }
}