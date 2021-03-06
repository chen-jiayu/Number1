<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\user;
use App\workspace_user;
use DB;
use Hash;
use Illuminate\Http\RedirectResponseRedirectResponseredirect;

class loginController extends Controller
{
  public function __construct()
  {
        // 所有 method 都會先經過 auth 這個 middleware
        //$this->middleware('returnid');

        // 只有 create 會經過 auth 這個 middleware
      //  $this->middleware('auth',['only' => 'create']);

        // 除了 index 之外都會先經過 auth 這個 middleware
    $this->middleware('returnid',['except' => ['store' ,'checktoken']]);
    

  }
    //登入
  public function checktoken(Request $request) {
    try {
      DB::connection()->getPdo()->beginTransaction();
      $credentials = request(['citizen_id', 'password']);
      $id= DB::table('users')->where('citizen_id', '=',$request->input('citizen_id') )->value('id');
      $id_token= DB::table('users')->where('citizen_id', '=',$request->input('citizen_id') )->value('id_token');

        if (!$token = auth('api')->attempt($credentials)) {
          return response()->json([
            'status' => '0',
            'code'=>2,
            'message'=>'data not found'
          ]);
        }
        else{
          $user = user::find($id);
          $user->remeber_token=$token;
          $user->save();
        }
        DB::connection()->getPdo()->commit();
        return response()->json([
          'status' => '1',
          'id_token' => $id_token,
          'remember_token' => $token,
          'expires' => auth('api')->factory()->getTTL() * 60,
        ]);

    } catch (\PDOException $e) {
      DB::connection()->getPdo()->rollBack();
      return response()->json([
        'status' => '0',
        'code'=>0,
        'message'=>$e->getMessage()

      ]);
    }
  }
  
    //建立新user
  public function store(Request $request)   
  {
    try {
    //  $this->validate($request,[
    //   'user_name'=>'required',
    //   'mobile'=>'required',
    //   'email'=>'required',
    //   'citizen_id'=>'required',
    //   'password'=>'required'
    // ]); 
     DB::connection()->getPdo()->beginTransaction();
     $count=DB::table('users')->where('citizen_id', '=',$request->input('citizen_id'))->count();
     if($count== 0){
       $id_token=bcrypt($request->input('citizen_id'));
       $user = new user();
       $user->user_name= $request->input('user_name');
       $user->mobile= $request->input('mobile');
       $user->email= $request->input('email');
       $user->workspace_id= NULL;
       $user->citizen_id= $request->input('citizen_id');
       $user->password= bcrypt($request->input('password'));
       $user->id_token= $id_token;
       $user->save();
       DB::connection()->getPdo()->commit();
       return response()->json([
        'id_token' => $id_token,
        'status' => '1'

      ]);}
       else
        return response()->json([
          'status' => '0',
          'code'=>3,
          'message'=>'data duplicate'
        ]);
    } catch (\PDOException $e) {
      DB::connection()->getPdo()->rollBack();
      return response()->json([
        'status' => '0',
        'code'=>0,
        'message'=>$e->getMessage()

      ]);
    }
  }
  // public function storerem_token($id_token,$token) {
  //   $id= DB::table('users')->where('id_token', '=',$id_token )->value('id');
  //   $user = user::find($id);
  //   $user->remeber_token=$token;
  //   $user->save();
  // }
  

    //修改資料
  public function update (Request $request){
    try{
      DB::connection()->getPdo()->beginTransaction();
      $id=$request->get('remeber_token');
      $count=DB::table('users')->where('id', '=',$id)->count();
      
      $user = User::find($id);

        if($count==0){
          return response()->json([
            'status' => '0',
            'code'=>2,
            'message'=>'data not found'
          ]);
        }
        $user->user_name = $request->input('user_name');
        $user->mobile = $request->input('mobile');
        $user->email = $request->input('email');
        $user->save();
        DB::connection()->getPdo()->commit();
        return response()->json([
          'status' => '1'
        ]);
     
    } catch (\PDOException $e) {
      DB::connection()->getPdo()->rollBack();
      return response()->json([
        'status' => '0',
        'code'=>0,
        'message'=>$e->getMessage()

      ]);
    }
  }


    //修改密碼
  public function updatepassword (Request $request){
    try{
      DB::connection()->getPdo()->beginTransaction();
      $id=$request->get('remeber_token');
      $old=$request->input('oldpassword');
      
      $user = User::find($id);
        if(Hash::check($old,$user->password)){
        	$user->password = bcrypt($request->input('newpassword')); //舊密碼
        	$user->save();
          DB::connection()->getPdo()->commit();
        	return response()->json([
            'status' => '1'
          ]);	
        }
        else{
        	return response()->json([
            'status' => '0',
            'code'=>2,
            'message'=>'data not found'
          ]);
        }
    } catch (\PDOException $e) {
      DB::connection()->getPdo()->rollBack();
      return response()->json([
        'status' => '0',
        'code'=>0,
        'message'=>$e->getMessage()

      ]);
    }
  }
}
