<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Event;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //
    public function index(){
        $totalEvent = Event::where('user_id',auth()->id())->count();
        $totalTask = Task::where('user_id',auth()->id())->count();
        $totalClient = Client::where('user_id',auth()->id())->count();
        return view('content.pages.dashboard',compact('totalEvent','totalTask','totalClient'));
    }


    public function users(Request $request){
        $columns = [
            1 => 'id',
            2 => 'name',
            3 => 'email',
          ];
      
          $search = [];
      
          $totalData = User::count();
      
          $totalFiltered = $totalData;
      
          $limit = $request->input('length');
          $start = $request->input('start');
          $order = $columns[$request->input('order.0.column')];
          $dir = $request->input('order.0.dir');
      
          if (empty($request->input('search.value'))) {
            $clients = User::offset($start)
              ->limit($limit)
              ->orderBy($order, $dir)
              ->get();
          } else {
            $search = $request->input('search.value');
      
            $clients = User::where('id', 'LIKE', "%{$search}%")
              ->orWhere('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->offset($start)
              ->limit($limit)
              ->orderBy($order, $dir)
              ->get();
      
            $totalFiltered = User::where('id', 'LIKE', "%{$search}%")
              ->orWhere('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->count();
          }
      
          $data = [];
      
          if (!empty($clients)) {
            // providing a dummy id instead of database ids
            $ids = $start;
      
            foreach ($clients as $client) {
                $nestedData['id'] = $client->id;
                $nestedData['fake_id'] = ++$ids;
                $nestedData['name'] = $client->name;
                $nestedData['email'] = $client->email;

      
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
}
