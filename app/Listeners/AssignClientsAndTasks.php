<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\Client;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class AssignClientsAndTasks
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        //
        $user = $event->user;
        $clients = ['Cybenergie', 'Internal'];
        $tasks = ['Bank holiday', 'Vacation', 'Performance Review', 'Training', 'Workday'];
    
        // Use a database transaction
        DB::transaction(function () use ($user, $clients, $tasks) {
            try {
                // Assign clients to the user
                foreach ($clients as $clientName) {
                    $client = new Client(['company' => $clientName]);
                    $user->clients()->save($client);
                }
    
                // Assign tasks to the user
                foreach ($tasks as $task) {
                    $taskModel = new Task([
                        'title' => $task,
                        'color' => '#edd38a',
                        'client_id' => $client->id,  
                        'is_billable' => false
                    ]);
                    $user->tasks()->save($taskModel);
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }
}
