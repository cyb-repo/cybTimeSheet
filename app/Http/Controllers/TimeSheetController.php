<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class TimeSheetController extends Controller
{
    //
    public function index()
    {
        $tasks = Task::where('user_id', Auth::user()->id)->get();
        return view('content.pages.timesheet', compact('tasks'));
    }

    public function events(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $events = Event::where('user_id', Auth::user()->id)->whereBetween('start', [$start, $end])->whereBetween('end', [$start, $end])->get();

        $tasks = [];
        foreach ($events as $event) {
            $tasks[] = [
                'id' => $event->id,
                'title' => $event->task->title,
                'start' => $event->start,
                'end' => $event->end,
                'color' => $event->task->color,
                'allDay' => $event->all_day ? true : false,
                "extendedProps" => [
                    "task_id" =>  $event->task_id,
                ]
            ];
        }
        return response()->json($tasks);
    }

    public function store(Request $request)
    {

        try {
            Event::create([
                'task_id' => $request->task_id,
                'start' => $request->start,
                'end' => $request->end,
                // 'color' => $request->color,
                'all_day' => $request->allDay == 'true' ? 1 : 0,
                'user_id' => Auth::user()->id
            ]);
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()]);
        }
    }

    public function update(Request $request)
    {

        try {
            $event = Event::find($request->id);
            $start = $request->start;
            $end =  $request->end;
            $start = Carbon::parse($start)->format('Y-m-d H:i:s');
            $end = Carbon::parse($end)->format('Y-m-d H:i:s');
            $event->update([
                'task_id' => $request->task_id,
                'start' => $start,
                'end' => $end,
                //'color' => $request->color,
                'all_day' => $request->allDay == 'true' ? 1 : 0,
            ]);
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()]);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $event = Event::findOrFail($request->id);
            $event->delete();
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()]);
        }
    }
    public function copy(Request $request)
    {
        //copy last week events to this week

        $events = Event::where('user_id', Auth::user()->id)
            ->whereBetween('start', [Carbon::now()->subWeek(), Carbon::now()])
            ->WhereBetween('end', [Carbon::now()->subWeek(), Carbon::now()])
            ->get();

        foreach ($events as $event) {
            $start = Carbon::parse($event->start)->addWeek();
            $end = Carbon::parse($event->end)->addWeek();
            Event::create([
                'task_id' => $event->task_id,
                'start' => $start,
                'end' => $end,
                //   'color' => $event->color,
                'all_day' => $event->all_day,
                'user_id' => Auth::user()->id
            ]);
        }

        return response()->json(['status' => 'success'], 200);
    }
    public function workday(Request $request)
    {

        $task = Task::where('title', 'Workday')->where('user_id', auth()->id())->first();
        if (!$task) {
            $client =  Client::where('user_id', auth()->id())->first();
            if (!$client) return response()->json(['status' => 'error'], 404);
            $task =  Task::create([
                'title' => 'Workday',
                'color' => '#edd38a',
                'client_id' => $client->id,
                'is_billable' => false,
                'user_id' => auth()->id(),
            ]);
        }
        // Define the time intervals for workday events
        $morningStart = Carbon::parse('8:00 AM');
        $morningEnd = Carbon::parse('12:00 PM');
        $afternoonStart = Carbon::parse('1:00 PM');
        $afternoonEnd = Carbon::parse('5:00 PM');

        // Calculate the start of the current week
        $startOfWeek = Carbon::now()->startOfWeek()->setTime(0, 0, 0);

        // Loop through each weekday (from Monday to Friday)
        for ($i = 0; $i <= 4; $i++) {
            $currentDay = $startOfWeek->copy()->addDays($i);

            // Check if events already exist for the current day
            $existingMorningEvent = Event::where('user_id', Auth::user()->id)
                ->where('start', $currentDay->copy()->setTimeFrom($morningStart))
                ->where('end', $currentDay->copy()->setTimeFrom($morningEnd))
                ->count();

            $existingAfternoonEvent = Event::where('user_id', Auth::user()->id)
                ->where('start', $currentDay->copy()->setTimeFrom($afternoonStart))
                ->where('end', $currentDay->copy()->setTimeFrom($afternoonEnd))
                ->count();

            if ($existingMorningEvent == 0) {
                // Add morning event
                Event::create([
                    'start' => $currentDay->copy()->setTime($morningStart->hour, $morningStart->minute, 0),
                    'end' => $currentDay->copy()->setTime($morningEnd->hour, $morningEnd->minute, 0),
                    'user_id' => Auth::user()->id,
                    'task_id' => $task->id,
                    'all_day' => false
                    // Add other necessary fields
                ]);
            }

            if ($existingAfternoonEvent == 0) {
                // Add afternoon event
                Event::create([
                    'start' => $currentDay->copy()->setTime($afternoonStart->hour, $afternoonStart->minute, 0),
                    'end' => $currentDay->copy()->setTime($afternoonEnd->hour, $afternoonEnd->minute, 0),
                    'user_id' => Auth::user()->id,
                    'task_id' => $task->id,
                    'all_day' => false
                    // Add other necessary fields
                ]);
            }
        }


        return response()->json(['status' => 'success'], 200);
    }

    public function AdminClient($userId)
    {
        $users = User::all();
        $tasks = Task::where('user_id', $userId)->get();
        return view('content.pages.add-client-admin', compact('users', 'tasks', 'userId'));
    }

    public function AdminClientStore(Request $request, $userId)
    {

        $request->validate([
            'company' => 'required',
        ]);
        try {
            $clinet = Client::create(
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'company' => $request->company,
                    'remark' => $request->remark,
                    'user_id' => $userId,
                ]
            );

            session()->flash('created');
            return redirect()->back();
        } catch (\Exception $exception) {
            return response()->json(['status' => 'error', 'error' => $exception->getMessage()]);
        }
    }
}
