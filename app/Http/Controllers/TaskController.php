<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    //

    public function index()
    {
        $totalTask = Task::where('user_id', auth()->user()->id)->count();
        $clients = Client::where('user_id', auth()->user()->id)->get();
        $users = User::all();
        return view('content.pages.task', compact('totalTask', 'clients','users'));
    }

    public function tasks(Request $request)
    {
        $columns = [
            1 => 'id',
            2 => 'client_id',
            3 => 'title',
            4 => 'remark',
            6 => 'cost_center',
            7 => 'is_billable',
        ];

        $search = [];

        $totalData = Task::where('user_id', auth()->user()->id)->count();

        $totalFiltered = $totalData;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if (empty($request->input('search.value'))) {
            $tasks = Task::where('user_id', auth()->user()->id)
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();
        } else {
            $search = $request->input('search.value');

            $tasks = Task::where('user_id', auth()->user()->id)
                ->where('id', 'LIKE', "%{$search}%")
                ->orWhere('title', 'LIKE', "%{$search}%")
                ->orWhere('cost_center', 'LIKE', "%{$search}%")
                ->orWhere('is_billable', 'LIKE', "%{$search}%")
                ->orWhere('remark', 'LIKE', "%{$search}%")
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $totalFiltered = Task::where('user_id', auth()->user()->id)
                ->where('id', 'LIKE', "%{$search}%")
                ->orWhere('title', 'LIKE', "%{$search}%")
                ->orWhere('cost_center', 'LIKE', "%{$search}%")
                ->orWhere('is_billable', 'LIKE', "%{$search}%")
                ->orWhere('remark', 'LIKE', "%{$search}%")
                ->count();
        }

        $data = [];

        if (!empty($tasks)) {
            // providing a dummy id instead of database ids
            $ids = $start;

            foreach ($tasks as $task) {
                $nestedData['fake_id'] = ++$ids;
                $nestedData['id'] = $task->id;
                $nestedData['title'] = $task->title;
                $nestedData['client'] = $task->client->company;
                $nestedData['cost_center'] = $task->cost_center;
                $nestedData['color'] = $task->color;
                $nestedData['is_billable'] = $task->is_billable;
                $nestedData['remark'] = $task->remark;


                $data[] = $nestedData;
            }
        }

        if ($data) {
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'code' => 200,
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'message' => 'Internal Server Error',
                'code' => 500,
                'data' => [],
            ]);
        }
    }

    public function store(Request $request)
    {
        $task_id = $request->id;

        if ($task_id) {
            // update the value
            $task = Task::updateOrCreate(
                ['id' => $task_id],
                [
                    'title' => $request->title,
                    'client_id' => $request->client,
                    'user_id' => auth()->user()->id,
                    'cost_center' => $request->costcenter,
                    'color' => $request->color,
                    'is_billable' => $request->billable == 'on' ? 1 : 0,
                    'remark' => $request->remark,
                ]
            );
            // user updated
            return response()->json('Updated');
        } else {
            // create a new value
            $task = Task::create([
                'title' => $request->title,
                'client_id' => $request->client,
                'user_id' => auth()->user()->id,
                'cost_center' => $request->costcenter,
                'color' => $request->color,
                'is_billable' => $request->billable == 'on' ? 1 : 0,
                'remark' => $request->remark,
            ]);
            // user created
            return response()->json('Created');
        }
    }

    public function edit($id)
    {
        $client = Task::where('id', $id)->first();

        return response()->json($client);
    }

    public function destroy($id)
    {
        $client = Task::where('id', $id)->delete();

        return response()->json('Deleted');
    }


    public function AdminAddTask($userId){
     
        $clients = Client::where('user_id', auth()->user()->id)->get();
        $users = User::all();
        return view('content.pages.add-task-admin', compact( 'clients','users','userId'));
    }

    public function AdminStoreTask(Request $request){
        $request->validate([
            'user' => 'required',
            'title' => 'required',
            'client' => 'required',
            'color' => 'required',
        ]);
       
      
            // create a new value
            $task = Task::create([
                'title' => $request->title,
                'client_id' => $request->client,
                'user_id' => $request->user,
                'cost_center' => $request->costcenter,
                'color' => $request->color,
                'is_billable' => $request->billable == 'on' ? 1 : 0,
                'remark' => $request->remark,
            ]);
            // user created
           session()->flash('created');

           return redirect()->back();
        
    }


  
   
}
