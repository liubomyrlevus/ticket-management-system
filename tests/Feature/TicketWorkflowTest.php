<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;

class TicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_ticket_workflow_with_load_balancing()
    {

        $category = Category::create(['name' => 'IT Department']);
        $priority = Priority::create(['name' => 'Normal']);

        $client = User::factory()->create([
            'role' => 'client',
            'is_approved' => true
        ]);

        $busyStaff = User::factory()->create([
            'role' => 'staff',
            'category_id' => $category->id,
            'name' => 'Зайнятий Працівник'
        ]);

        $freeStaff = User::factory()->create([
            'role' => 'staff',
            'category_id' => $category->id,
            'name' => 'Вільний Працівник'
        ]);

        Ticket::create([
            'title' => 'Old task 1', 'description' => '...',
            'category_id' => $category->id, 'priority_id' => $priority->id,
            'client_id' => $client->id, 'staff_id' => $busyStaff->id, 'status' => 'New'
        ]);
        Ticket::create([
            'title' => 'Old task 2', 'description' => '...',
            'category_id' => $category->id, 'priority_id' => $priority->id,
            'client_id' => $client->id, 'staff_id' => $busyStaff->id, 'status' => 'In Progress'
        ]);

        $this->actingAs($client) 
             ->post(route('tickets.store'), [
                 'title' => 'Мій новий зламаний комп',
                 'description' => 'Допоможіть полагодити',
                 'category_id' => $category->id,
                 'priority_id' => $priority->id,
             ])
             ->assertRedirect(route('tickets.index')) 
             ->assertSessionHas('success'); 

        $newTicket = Ticket::where('title', 'Мій новий зламаний комп')->first();


        $this->assertNotNull($newTicket);
        
        $this->assertEquals($freeStaff->id, $newTicket->staff_id);

        
        $this->actingAs($freeStaff) 
             ->patch(route('tickets.updateStatus', $newTicket->id), [
                 'status' => 'Resolved'
             ])
             ->assertRedirect(); 

        $newTicket->refresh();
        
        $this->assertEquals('Resolved', $newTicket->status);


        $this->actingAs($client) 
             ->get(route('tickets.show', $newTicket->id)) 
             ->assertStatus(200)
             ->assertSee('Resolved') 
             ->assertSee($freeStaff->name); 
    }
    public function test_admin_manual_assignment_workflow()
    {

        $emptyCategory = Category::create(['name' => 'New Empty Dept']);
        $otherCategory = Category::create(['name' => 'Other Dept']);
        $priority = Priority::create(['name' => 'High']);

        $client = User::factory()->create([
            'role' => 'client', 
            'is_approved' => true
        ]);
        
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);
        
        $specialist = User::factory()->create([
            'role' => 'staff',
            'category_id' => $otherCategory->id, 
            'name' => 'Олег Рятівник'
        ]);

        $this->actingAs($client)
             ->post(route('tickets.store'), [
                 'title' => 'Проблема у порожньому відділі',
                 'description' => 'Допоможіть!',
                 'category_id' => $emptyCategory->id,
                 'priority_id' => $priority->id,
             ])
             ->assertRedirect(route('tickets.index'));

        $ticket = Ticket::where('title', 'Проблема у порожньому відділі')->first();
        
        $this->assertNotNull($ticket);
        $this->assertNull($ticket->staff_id);


        $this->actingAs($admin)
             ->from(route('tickets.show', $ticket->id)) 
             ->put(route('tickets.update', $ticket->id), [
                 'staff_id' => $specialist->id
             ])
             ->assertRedirect(route('tickets.show', $ticket->id));

        $ticket->refresh(); 
        
        $this->assertEquals($specialist->id, $ticket->staff_id);

        $this->actingAs($specialist)
             ->from(route('tickets.show', $ticket->id))
             ->patch(route('tickets.updateStatus', $ticket->id), [
                 'status' => 'Resolved'
             ])
             ->assertRedirect(route('tickets.show', $ticket->id));

        $ticket->refresh();
        
        $this->assertEquals('Resolved', $ticket->status);

        $this->actingAs($client)
             ->get(route('tickets.show', $ticket->id))
             ->assertStatus(200)
             ->assertSee('Resolved')
             ->assertSee($specialist->name);
    }
    public function test_specialist_rejects_and_admin_reassigns()
    {

        $category = Category::create(['name' => 'Technical Support']);
        $priority = Priority::create(['name' => 'Urgent']);

        $client = User::factory()->create(['role' => 'client', 'is_approved' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        
        $staff1 = User::factory()->create(['role' => 'staff', 'category_id' => $category->id, 'name' => 'Працівник 1']);
        $staff2 = User::factory()->create(['role' => 'staff', 'category_id' => $category->id, 'name' => 'Працівник 2']);

        Ticket::create([
            'title' => 'Some old task', 'description' => '...',
            'category_id' => $category->id, 'priority_id' => $priority->id,
            'client_id' => $client->id, 'staff_id' => $staff2->id, 'status' => 'New'
        ]);


        $this->actingAs($client)
             ->post(route('tickets.store'), [
                 'title' => 'Зламався магістральний роутер',
                 'description' => 'Немає інтернету в усьому офісі!',
                 'category_id' => $category->id,
                 'priority_id' => $priority->id,
             ]);

        $ticket = Ticket::where('title', 'Зламався магістральний роутер')->first();
        
        $this->assertEquals($staff1->id, $ticket->staff_id);


        $this->actingAs($staff1)
             ->from(route('tickets.show', $ticket->id))
             ->put(route('tickets.update', $ticket->id), [
                 'quick_action' => 'release',
                 'action_comment' => 'Я джуніор, не маю доступу до магістральних роутерів. Передайте комусь іншому.'
             ])
             ->assertRedirect(route('tickets.index'))
             ->assertSessionHas('success');

        $ticket->refresh();
        
        $this->assertNull($ticket->staff_id);

        $this->assertDatabaseHas('comments', [
            'ticket_id' => $ticket->id,
            'content' => 'Released the ticket. Reason: Я джуніор, не маю доступу до магістральних роутерів. Передайте комусь іншому.'
        ]);


        $this->actingAs($admin)
             ->from(route('tickets.show', $ticket->id))
             ->put(route('tickets.update', $ticket->id), [
                 'staff_id' => $staff2->id 
             ])
             ->assertRedirect(route('tickets.show', $ticket->id));

        $ticket->refresh();
        
        $this->assertEquals($staff2->id, $ticket->staff_id);
    }
}