<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class HolidaysController extends Controller
{
    //


    public function index($userId){
        $users = User::all();
        $tasks = [];
        return view('content.pages.holidays',compact('users','tasks','userId'));
    }

    public function store(Request $request,$userId){
        $request->validate([
            'eventStartDate' => 'required',
            'eventEndDate' => 'required'
        ]);
        //create holiday client 
        $client =   Client::updateOrCreate([
            'email' => 'holiday@holiday.com'
        ],[
            'name' => 'holiday',
            'email' => 'holiday@holiday.com',
            'company' => 'holidays',
            'user_id' => auth()->id(),
        ]);

        $task = Task::updateOrCreate(['user_id' => $client->user_id],[
            'title' => 'Holiday',
            'color' => '#a8eb34',
            'client_id' => $client->id,
            'is_billable' => true,
        ]);

        try{
            Event::create([
                'task_id' => $task->id,
                'start' => $request->eventStartDate,
                'end' => $request->eventEndDate,
               // 'color' => $request->color,
                'all_day' => $request->allDay == 'true' ? 1 : 0,
                'user_id' => $userId
            ]);
            session()->flash('created');
            return redirect()->back();
           }
           catch (\Exception $exception){
               return response()->json(['status' => 'error','error' => $exception->getMessage()]);
           }
    }
}
