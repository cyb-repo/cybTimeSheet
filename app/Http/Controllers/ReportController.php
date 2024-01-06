<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    //
    public function index()
    {
        $clients = Client::where('user_id',auth()->id())->get();
        return view('content.pages.report',compact('clients'));
    }

    public function download($duration, $date,$client)
    {

        
        if ($duration == 'weekly') {
            if($client == 0)
              $task = Event::where('user_id',auth()->id())->where('start', '>=', date('Y-m-d', strtotime('monday this week')))->where('end', '<=', date('Y-m-d', strtotime('monday this week +7 days')))->orderBy('start','asc')->get();
            else
            $task = Event::join('tasks', 'events.task_id', '=', 'tasks.id')
                    ->join('clients', 'tasks.client_id', '=', 'clients.id')
                    ->where('events.user_id', auth()->id())
                    ->where('clients.id', $client)
                    ->where('events.start', '>=', date('Y-m-d', strtotime('monday this week')))
                    ->where('events.end', '<=', date('Y-m-d', strtotime('monday this week +7 days')))
                    ->orderBy('events.start', 'asc')
                    ->select('events.*') // You may need to specify the columns you want to select from the events table
                    ->get();
            }
        if ($duration == 'monthly') {
            if ($date == 'no') {
                if($client == 0)
                   $task = Event::where('user_id',auth()->id())->where('start', '>=', date('Y-m-d', strtotime('first day of this month')))->where('end', '<=', date('Y-m-d', strtotime('first day of next month')))->orderBy('start','asc')->get();
                else
                $task = Event::join('tasks', 'events.task_id', '=', 'tasks.id')
                    ->join('clients', 'tasks.client_id', '=', 'clients.id')
                    ->where('events.user_id', auth()->id())
                    ->where('clients.id', $client)
                    ->where('start', '>=', date('Y-m-d', strtotime('first day of this month')))
                    ->where('end', '<=', date('Y-m-d', strtotime('first day of next month')))
                    ->orderBy('events.start', 'asc')
                    ->select('events.*') // You may need to specify the columns you want to select from the events table
                    ->get();
            } else {
                $startOfMonth = date('Y-m-01', strtotime($date));
                $endOfMonth = date('Y-m-t', strtotime($date));
                if($client == 0)
                {
                    $task = Event::where('user_id',auth()->id())
                        ->where('start', '>=', $startOfMonth)
                        ->where('end', '<=', $endOfMonth)
                        ->orderBy('start','asc')
                        ->get();
                }else{
                    $task = Event::join('tasks', 'events.task_id', '=', 'tasks.id')
                    ->join('clients', 'tasks.client_id', '=', 'clients.id')
                    ->where('events.user_id', auth()->id())
                    ->where('clients.id', $client)
                    ->where('start', '>=', $startOfMonth)
                    ->where('end', '<=', $endOfMonth)
                    ->orderBy('events.start', 'asc')
                    ->select('events.*') // You may need to specify the columns you want to select from the events table
                    ->get();
                }
               
            }
        }
        if ($duration == 'yearly') {
            $year = date('Y', strtotime($date));
            if($client == 0){
                $task = Event::where('user_id',auth()->id())
                    ->where('start', '>=', $year . '-01-01')
                    ->where('end', '<=', $year . '-12-31')
                    ->orderBy('start','asc')
                    ->get();
            }else{
                $task = Event::join('tasks', 'events.task_id', '=', 'tasks.id')
                ->join('clients', 'tasks.client_id', '=', 'clients.id')
                ->where('events.user_id', auth()->id())
                ->where('clients.id', $client)
                ->where('start', '>=', $year . '-01-01')
                ->where('end', '<=', $year . '-12-31')
                ->orderBy('events.start', 'asc')
                ->select('events.*') // You may need to specify the columns you want to select from the events table
                ->get();
            }
        
            //$task = Event::where('start','>=',date('Y-m-d',strtotime('first day of january this year')))->where('end','<=',date('Y-m-d',strtotime('last day of december this year')))->get();
        }

        //download as csv 
        //date,task,time started,time ended,time duration,billable,cost center,client,remark
        $data = [];
        foreach ($task as $t) {
            //duration is the event start and end date time difference
            $date_start = $t->start;
            $date_end = $t->end;
            $date_diff = strtotime($date_end) - strtotime($date_start);
            //hours
            $hours = floor($date_diff / (60 * 60));
            //minutes
            $minutes = floor(($date_diff - ($hours * 60 * 60)) / 60);
            //seconds
            $seconds = $date_diff - ($hours * 60 * 60) - ($minutes * 60);
            $t->duration = $hours . ':' . $minutes . ':' . $seconds;
            $data[] = [

                'date_started' => Carbon::parse($t->start)->format('Y-m-d'),
                // 'created_at' => $t->created_at,
                'task' => $t->task->title,
                // 'date_ended' => Carbon::parse($t->end)->format('Y-m-d'),
                'time_started' => Carbon::parse($t->start)->format('H:i:s') . ' ' . /*AM OR PM */ Carbon::parse($t->start)->format('A'),
                'time_ended' => Carbon::parse($t->end)->format('H:i:s') . ' ' . /*AM OR PM */ Carbon::parse($t->end)->format('A'),
                'time_duration' => $t->duration,
                'billable' => $t->task->is_billable,
                'cost_center' => $t->task->cost_center,
                'client' => $t->task->client->company,
                'remark' => $t->task->remark,
            ];
        }

        // dd($data);
        $filename = "report" . date('Y-m-d') . ".csv";
        $handle = fopen($filename, 'w+');
        fputcsv($handle, array('date_started', 'task', 'time_started', 'time_ended', 'time_duration', 'billable', 'cost_center', 'client', 'remark'));

        foreach ($data as $row) {
            fputcsv($handle, array( $row['date_started'],$row['task'], $row['time_started'], $row['time_ended'], $row['time_duration'], $row['billable'], $row['cost_center'], $row['client'], $row['remark']));
        }

        fclose($handle);

        $headers = array(
            'Content-Type' => 'text/csv',
        );

        return response()->download($filename, $filename, $headers);
    }
}
