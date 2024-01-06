<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    //
    public function index(){
        $totalClient = Client::where('user_id', auth()->user()->id)->count();
        return view('content.pages.client',compact('totalClient'));
    }

    public function clients(Request $request){
        $columns = [
            1 => 'id',
            2 => 'name',
            3 => 'email',
            4 => 'company',
            5 => 'remark',
          ];
      
          $search = [];
          $holiday_id = Client::where('email','=','holiday@holiday.com')->first();
          if($holiday_id){
            $holiday_id = $holiday_id->id;
          }else{
            $holiday_id = 0;
          }
          $totalData = Client::where('user_id', auth()->user()->id)->where('id','!=',$holiday_id)->count();
      
          $totalFiltered = $totalData;
      
          $limit = $request->input('length');
          $start = $request->input('start');
          $order = $columns[$request->input('order.0.column')];
          $dir = $request->input('order.0.dir');
      
          if (empty($request->input('search.value'))) {
            $clients = Client::where('user_id', auth()->user()->id)
              ->where('id','!=',$holiday_id)
              ->offset($start)
              ->limit($limit)
              ->orderBy($order, $dir)
              ->get();
          } else {
            $search = $request->input('search.value');
      
            $clients = Client::where('user_id', auth()->user()->id)
              ->where('id','!=',$holiday_id)
              ->where('id', 'LIKE', "%{$search}%")
              ->orWhere('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('company', 'LIKE', "%{$search}%")
              ->orWhere('remark', 'LIKE', "%{$search}%")
              ->offset($start)
              ->limit($limit)
              ->orderBy($order, $dir)
              ->get();
      
            $totalFiltered = Client::where('user_id', auth()->user()->id)
             
              ->where('id', 'LIKE', "%{$search}%")
              ->orWhere('name', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('company', 'LIKE', "%{$search}%")
              ->orWhere('remark', 'LIKE', "%{$search}%")
              ->where('id','!=',$holiday_id)
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
                $nestedData['company'] = $client->company;
                $nestedData['remark'] = $client->remark;

      
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

    public function store(Request $request){
        $userID = $request->id;

        if ($userID) {
          // update the value
          $clinet = Client::updateOrCreate(
            ['id' => $userID],
            ['name' => $request->name,
             'email' => $request->email,
             'company' => $request->company,
             'remark' => $request->remark,
             'user_id' => auth()->user()->id
            ]
          );
    
          // user updated
          return response()->json('Updated');
        } else {
          //$userEmail = Client::where('email', $request->email)->where('user_id',auth()->id())->first();
          $clinet = Client::updateOrCreate(
            ['id' => $userID],
            ['name' => $request->name, 'email' => $request->email, 'company' => $request->company, 'remark' => $request->remark, 'user_id' => auth()->user()->id]
          );
  
          // client created
          return response()->json('Created');
          // if (empty($userEmail)) {
          //   $clinet = Client::updateOrCreate(
          //     ['id' => $userID],
          //     ['name' => $request->name, 'email' => $request->email, 'company' => $request->company, 'remark' => $request->remark, 'user_id' => auth()->user()->id]
          //   );
    
          //   // client created
          //   return response()->json('Created');
          // } else {
          //   // user already exist
          //   return response()->json(['message' => "Client Email already exits"], 422);
          // }
        }
    }

    public function edit($id){
        $client = Client::where('id', $id)->first();
        return response()->json($client);
    }

    public function destroy($id){
        $client = Client::where('id', $id)->delete();
        return response()->json('Deleted');
    }
}
