<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Billdata;
use App\Models\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BillDataHistoryController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Log::info('billdatahistory index START');

        // ログインユーザーのユーザー情報を取得する
        $user    = $this->auth_user_info();
        $userid  = $user->id;
        $organization_id =  $user->organization_id;

        // Customer(複数レコード)情報を取得する
        $customer_findrec = $this->auth_customer_findrec();
        $customer_id = $customer_findrec[0]['id'];

        $indiv_class = $customer_findrec[0]['individual_class'];

        // Log::debug('billdatahistory index  organization_id = ' . print_r($organization_id,true));

        // Customer(all)情報を取得する
        if($organization_id == 0) {
            $customers = Customer::where('organization_id','>=',$organization_id)
                        ->where('active_cancel','!=', 3)
                        ->whereNull('deleted_at')
                        ->orderBy('individual_class', 'asc')
                        ->orderBy('business_name', 'asc')
                        ->get();
        } else {
            $customers = Customer::where('organization_id','=',$organization_id)
                        ->where('active_cancel','!=', 3)
                        ->whereNull('deleted_at')
                        ->orderBy('individual_class', 'asc')
                        ->orderBy('business_name', 'asc')
                        ->get();
        }

        $billdatas = Billdata::where('extension_flg',2)
                    ->whereNull('deleted_at')
                    ->orderByRaw('created_at DESC')
                    ->sortable()
                    ->paginate(300);

        $keyword  = null;
        $keyword2 = null;
        $common_no = '06_2';
        Log::info('billdatahistory index END');

        $compacts = compact( 'common_no','indiv_class','billdatas','customers','customer_findrec','customer_id','keyword','keyword2','userid' );

        return view( 'billdatahistory.index', $compacts );
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function serch(Request $request)
    {
        Log::info('billdatahistory serch START');

        //-------------------------------------------------------------
        //- Request パラメータ
        //-------------------------------------------------------------
        $keyword = $request->Input('keyword');
        $customer_id = $request->Input('customer_id');

        // Customer(複数レコード)情報を取得する
        $customer_findrec = $this->auth_customer_findrec();

        $customers = Customer::where('id',$customer_id)
            ->orderBy('id', 'asc')
            ->first();

        $indiv_class = $customers->individual_class;

        // ログインユーザーのユーザー情報を取得する
        $user   = $this->auth_user_info();
        $userid = $user->id;
        $organization_id =  $user->organization_id;

        // 日付が入力された
        if($keyword) {
            $billdatas = Billdata::where('customer_id',$customer_id)
                ->whereNull('deleted_at')
                ->where('extension_flg',2)
                // ($keyword)日付の絞り込み
                ->whereDate('created_at',$keyword)
                ->orderByRaw('created_at DESC')
                ->sortable()
                ->paginate(300);
        } else {
            $billdatas = Billdata::where('extension_flg',2)
                ->whereNull('deleted_at')
                ->orderByRaw('created_at DESC')
                ->sortable()
                ->paginate(300);
        };

        // Customer(all)情報を取得する
        if($organization_id == 0) {
            $customers = Customer::where('organization_id','>=',$organization_id)
                        ->where('active_cancel','!=', 3)
                        ->whereNull('deleted_at')
                        ->orderBy('individual_class', 'asc')
                        ->orderBy('business_name', 'asc')
                        ->get();
        } else {
            $customers = Customer::where('organization_id','=',$organization_id)
                        ->where('active_cancel','!=', 3)
                        ->whereNull('deleted_at')
                        ->orderBy('individual_class', 'asc')
                        ->orderBy('business_name', 'asc')
                        ->get();
        }

        $common_no = '06_2';

        $compacts = compact( 'common_no','indiv_class','billdatas','customers','customer_findrec','customer_id','keyword','userid' );

        Log::info('billdatahistory serch END');
        return view( 'billdatahistory.index', $compacts );
    }

    /**
     * Display a listing of the resource.
     * 未使用
     * @return \Illuminate\Http\Response
     */
    public function serch_custom(Billdata $billdata, Request $request)
    {
        Log::info('billdatahistory serch_custom START');

        //-------------------------------------------------------------
        //- Request パラメータ
        //-------------------------------------------------------------
        $customer_id = $request->Input('customer_id');

        // ログインユーザーのユーザー情報を取得する
        $user   = $this->auth_user_info();
        $userid = $user->id;
        $organization_id =  $user->organization_id;

        // 顧客が選択された
        if($customer_id) {
            $billdatas = Billdata::where('customer_id',$customer_id)
                ->whereNull('deleted_at')
                // ($keyword)顧客の絞り込み
                ->where('extension_flg',2)
                ->orderByRaw('created_at DESC')
                ->sortable()
                ->paginate(300);
        } else {
            $billdatas = Billdata::where('extension_flg',2)
                ->whereNull('deleted_at')
                ->orderByRaw('created_at DESC')
                ->sortable()
                ->paginate(300);
        };

        // Customer(複数レコード)情報を取得する
        $customer_findrec = $this->auth_customer_findrec();

        // Customer(all)情報を取得する
        if($organization_id == 0) {
            $customers = Customer::whereNull('deleted_at');
                            // ->sortable()
                            // ->paginate(10);
        } else {
            $customers = Customer::where('organization_id','=',$organization_id)
                            ->whereNull('deleted_at');
                            // ->sortable()
                            // ->paginate(10);
        }

        $common_no = '06_2';
        $keyword  = null;
        $keyword2 = null;
        $compacts = compact( 'common_no','billdatas','customers','customer_findrec','customer_id','keyword','keyword2','userid' );

        Log::info('billdatahistory serch_custom END');
        return view( 'billdatahistory.index', $compacts );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show_up01($id)
    {
        Log::info('billdatahistory show_up01 START');

        $billdatas = Billdata::where('id',$id)
                    ->first();

        $disk = 'local';  // or 's3'
        $storage = Storage::disk($disk);
        // /var/www/html/storage/app/public/invoice/xls/folder0001/20231011_合同会社グローアップ_00001_請求書.pdf
        $str  = $billdatas->filepath;
        $str2 = substr_replace($str, "", 26);       // /var/www/html/storage/app/
        $filepath = str_replace($str2, '', $str);   // public/invoice/xls/folder0001/20231011_合同会社グローアップ_00001_請求書.pdf
        // $filepath = $billdatas->filepath;   // public/billdata/user0171/2023年7月末-20230821T050250Z-001.pdf
        $filename = $billdatas->filename;   // 2023年7月末-20230821T050250Z-001.pdf
        $pdf_path = $filepath;

        Log::debug('billdatahistory show_up01  filename = ' . print_r($filename,true));
        Log::debug('billdatahistory show_up01  pdf_path = ' . print_r($pdf_path,true));

        $file = $storage->get($pdf_path);

        Log::info('billdatahistory show_up01 END');

        return response($file, 200)
            ->header('Content-Type', 'application/pdf')
            // ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * [webapi]billdataテーブルの更新
     */
    public function update_api(Request $request)
    {
        Log::info('billdatahistory update_api START');

        // Log::debug('update_api request = ' .print_r($request->all(),true));
        $id = $request->input('id');

        $urgent_flg     = 1;  // 1:既読 2:未読

        $counts = array();
        $update = [];
        $update['urgent_flg'] = $urgent_flg;
        $update['updated_at'] = date('Y-m-d H:i:s');
        // Log::debug('update_api billdatahistory update : ' . print_r($update,true));

        $status = array();
        DB::beginTransaction();
        Log::info('billdatahistory update_api beginTransaction - start');
        try{
            // 更新処理
            Billdata::where( 'id', $id )->update($update);

            $status = array( 'error_code' => 0, 'message'  => 'Your data has been changed!' );
            $counts = 1;
            DB::commit();
            Log::info('billdatahistory update_api beginTransaction - end');
        }
        catch(Exception $e){
            Log::error('billdatahistory update_api exception : ' . $e->getMessage());
            DB::rollback();
            Log::info('billdatahistory update_api beginTransaction - end(rollback)');
            echo "エラー：" . $e->getMessage();
            $counts = 0;
            $status = array( 'error_code' => 501, 'message'  => $e->getMessage() );
        }

        Log::info('billdatahistory update_api END');

        return response()->json([ compact('status','counts') ]);

    }

}
