<?php

namespace App\Http\Controllers;

use DateTime;
use App\Models\Billdata;
use App\Models\Customer;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

$request = Request::createFromGlobals();
use Flow\Config as FlowConfig;
use Flow\Request as FlowRequest;

class BillDateconfirmController extends Controller
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
        // ログインユーザーのユーザー情報を取得する
        $user  = $this->auth_user_info();
        $u_id = $user->id;
        $userid = $user->id;
        $organization_id =  $user->organization_id;

        Log::info('billdataconfirmindex START $user->name = ' . print_r($user->name ,true));

        // customersを取得
        if($organization_id == 0) {
            $customer_findrec = Customer::where('organization_id','>=',$organization_id)
                                // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                ->where('active_cancel','!=', 3)
                                ->whereNull('deleted_at')
                                ->orderBy('individual_class', 'asc')
                                ->orderBy('business_name', 'asc')
                                ->get();
        } else {
            $customer_findrec = Customer::where('organization_id','=',$organization_id)
                                // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                ->where('active_cancel','!=', 3)
                                ->whereNull('deleted_at')
                                ->orderBy('individual_class', 'asc')
                                ->orderBy('business_name', 'asc')
                                ->get();
        }
        $customer_id = $customer_findrec[0]['id'];

        Log::debug('billdataconfirmindex customer_id  = ' . print_r($customer_id ,true));

        // 法人/個人
        $indiv_class = $customer_findrec[0]['individual_class'];

        // * 今年の年を取得2
        $nowyear   = intval($this->get_now_year2());

        // 今月の月を取得
        $nowmonth = intval($this->get_now_month());

        // 請求書データを取得
        if($organization_id == 0) {
            $billdatas = Billdata::where('organization_id','>=',$organization_id)
                        // ->where('customer_id','=',$customer_id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->sortable()
                        ->paginate(300);
        } else {
            $billdatas = Billdata::where('organization_id','=',$organization_id)
                        // ->where('customer_id','=',$customer_id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->sortable()
                        ->paginate(300);
        }

        $jsonfile = storage_path() . "/tmp/billdata_info_status_". $customer_id. ".json";
            // Log::debug('billdataconfirmindex $jsonfile  = ' . print_r($jsonfile ,true));

        $keyword2  = null;
        $common_no = '06_1';
        $compacts = compact('keyword2','nowyear','common_no','userid','billdatas', 'customer_findrec','customer_id','jsonfile' );

        Log::info('billdataconfirmindex END');

        return view( 'billdataconfirm.index', $compacts );

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function serch(Request $request)
    {
        Log::info('billdataconfirmserch START');

        //-------------------------------------------------------------
        //- Request パラメータ
        //-------------------------------------------------------------
        $customer_id = $request->Input('customer_id');

        // ログインユーザーのユーザー情報を取得する
        $user  = $this->auth_user_info();
        $u_id = $user->id;
        $userid = $user->id;
        $organization_id =  $user->organization_id;

        // customersを取得
        if($organization_id == 0) {
            $customer_findrec = Customer::where('organization_id','>=',$organization_id)
                                // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                ->where('active_cancel','!=', 3)
                                ->whereNull('deleted_at')
                                ->orderBy('individual_class', 'asc')
                                ->orderBy('business_name', 'asc')
                                ->get();
        } else {
            $customer_findrec = Customer::where('organization_id','=',$organization_id)
                                // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                ->where('active_cancel','!=', 3)
                                ->whereNull('deleted_at')
                                ->orderBy('individual_class', 'asc')
                                ->orderBy('business_name', 'asc')
                                ->get();
        }

        $customers = Customer::where('id',$customer_id)
                    ->orderBy('id', 'asc')
                    ->first();

        // * 今年の年を取得2
        $nowyear   = intval($this->get_now_year2());

        // 今月の月を取得
        $nowmonth = intval($this->get_now_month());

        // 請求書データを取得
        if($organization_id == 0) {
            $billdatas = Billdata::where('organization_id','>=',$organization_id)
                        ->where('customer_id','=',$customer_id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->sortable()
                        ->paginate(50);
        } else {
            $billdatas = Billdata::where('organization_id','=',$organization_id)
                        ->where('customer_id','=',$customer_id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->sortable()
                        ->paginate(50);
        }


        $jsonfile = storage_path() . "/tmp/billdata_info_status_". $customer_id. ".json";
        // Log::debug('billdataconfirmindex $jsonfile  = ' . print_r($jsonfile ,true));

        $keyword2  = null;
        $common_no = '06_1';
        $compacts = compact('keyword2','nowyear','common_no','userid','billdatas', 'customer_findrec','customer_id','jsonfile' );
    
        Log::info('billdataconfirmserch END');
        return view( 'billdataconfirm.index', $compacts );

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function serch_custom(Request $request)
    {
        Log::info('billdataconfirmserch_custom START');

        //-------------------------------------------------------------
        //- Request パラメータ
        //-------------------------------------------------------------
        $keyword = $request->Input('keyword');
        $keyyear = $request->Input('year');

        // ログインユーザーのユーザー情報を取得する
        $user    = $this->auth_user_info();
        $user_id = $user->id;
        $userid = $user_id;

        $organization  = $this->auth_user_organization();
        $organization_id = $organization->id;

        // * 今年の年を取得
        $nowyear    = intval($this->get_now_year());

        // 顧客名が入力された
        if( $keyword ) {
            if($organization_id == 0) {
                // customersを取得
                $customer_findrec = Customer::where('organization_id','>=',$organization_id)
                                    // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                    ->where('active_cancel','!=', 3)
                                    ->whereNull('deleted_at')
                                    ->where('business_name', 'like', "%$keyword%")
                                    ->orderBy('individual_class', 'asc')
                                    ->orderBy('business_name', 'asc')
                                    ->get();
            } else {
                // customersを取得
                $customer_findrec = Customer::where('organization_id','=',$organization_id)
                                    // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                    ->where('active_cancel','!=', 3)
                                    ->whereNull('deleted_at')
                                    ->where('business_name', 'like', "%$keyword%")
                                    ->orderBy('individual_class', 'asc')
                                    ->orderBy('business_name', 'asc')
                                    ->get();
            }
        } else {
            if($organization_id == 0) {
                // customersを取得
                $customer_findrec = Customer::where('organization_id','>=',$organization_id)
                                    // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                    ->where('active_cancel','!=', 3)
                                    ->whereNull('deleted_at')
                                    ->orderBy('individual_class', 'asc')
                                    ->orderBy('business_name', 'asc')
                                    ->get();

            } else {
                // customersを取得
                $customer_findrec = Customer::where('organization_id','=',$organization_id)
                                    // `active_cancel` int DEFAULT '1' COMMENT 'アクティブ/解約 1:契約 2:SPOT 3:解約',
                                    ->where('active_cancel','!=', 3)
                                    ->whereNull('deleted_at')
                                    ->orderBy('individual_class', 'asc')
                                    ->orderBy('business_name', 'asc')
                                    ->get();
            }    
        }
        if (isset($customer_findrec[0]['id'])) {
            $customer_id = $customer_findrec[0]['id'];
        } else {
            $customer_id = 0;
        }

        // 請求書データを取得
        if($organization_id == 0) {
            $billdatas = Billdata::where('organization_id','>=',$organization_id)
                        ->where('customer_id','=',$customer_id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->sortable()
                        ->paginate(50);
        } else {
            $billdatas = Billdata::where('organization_id','=',$organization_id)
                        ->where('customer_id','=',$customer_id)
                        ->whereNull('deleted_at')
                        ->orderBy('created_at', 'desc')
                        ->sortable()
                        ->paginate(50);
        }

        $common_no = '06_1';

        // * 選択された年を取得
        $nowyear   = $keyyear;
        $keyword2  = $keyword;

        // Log::debug('billdatars store $billdatars = ' . print_r($billdatars, true));
        $jsonfile = storage_path() . "/tmp/billdata_info_status_". $customer_id. ".json";

        $compacts = compact('nowyear', 'common_no','userid','billdatas', 'customer_findrec','customer_id','jsonfile','keyword2' );

        Log::info('billdatar serch_custom END');

        // return view('billdata.input', ['billdatas' => $billdatas]);
        return view('billdataconfirm.index', $compacts);
    }

    public function post(Request $data)
    {
        // Log::info('top post START');
        // Log::info('top post END');
        // // ホーム画面へリダイレクト
        // return redirect('/user');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show_up01()
    {
        Log::info('billdataconfirmshow_up01 インボイス START');

        // $disk = 'local';  // or 's3'
        // $storage = Storage::disk($disk);
        // $file_name = 'インボイス制度開始にあたってやるべきこと.pdf';
        // $pdf_path = 'public/pdf/' . $file_name;
        // $file = $storage->get($pdf_path);

        Log::info('billdataconfirmshow_up01 インボイス END');

        // return response($file, 200)
        //     ->header('Content-Type', 'application/pdf')
        //     // ->header('Content-Type', 'application/zip')
        //     ->header('Content-Disposition', 'inline; filename="' . $file_name . '"');

    }

    /**
     * postUpload_info uploaded file WEB ROUTE
     * @param Request request
     * @return JsonResponse
     */
    public function postUpload_info($customer_id)
    {
        Log::info('billdataconfirmpostUpload_info  START');

        // ログインユーザーのユーザー情報を取得する
        $user = $this->auth_user_info();
        $u_id = $user->id;
        $o_id = $user->organization_id;

        // 選択された顧客IDからCustomer情報(フォルダー名)を取得する
        $customers       = Customer::where('id',$customer_id)->first();
        $foldername      = $customers->foldername;
        $business_name   = $customers->business_name;
        $folderpath      = storage_path() . '/public' . '/billdata'. '/' . $foldername;

        // 年月取得
        $now = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $dateNew = ($now->format('Y/m'));

        $compacts = compact( 'u_id','o_id', 'customer_id', 'foldername','business_name','folderpath','dateNew' );

        Log::info('billdataconfirmpostUpload_info $compacts[customer_id]  = ' . print_r($compacts['customer_id'] ,true));

        // * ログインユーザーのCustomerオブジェクトをjsonにSetする
        $this->json_put_info_set($u_id, $o_id,$customer_id, $foldername, $business_name,$folderpath,$dateNew);

        Log::info('billdataconfirmpostUpload_info  END');

        return  $compacts;

    }

    /**
     * postUpload uploaded file WEB ROUTE
     * @param Request request
     * @return JsonResponse
     */
    public function postUpload($customer_id, Request $request)
    {
        Log::info('billdataconfirmpostUpload  START');

        $jsonfile = storage_path() . "/tmp/billdata_info_status_". $customer_id. ".json";
        $jsonUrl = $jsonfile; //JSONファイルの場所とファイル名を記述
        $status = true;
        if (file_exists($jsonUrl)) {
            $json = file_get_contents($jsonUrl);
            $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
            
            // 2023/09/20
            $obj = [];

            $obj = json_decode($json, true);

            // 2023/09/20
            if(empty($obj)){
                $obj[0] = $this->postUpload_info($customer_id);
                Log::info('billdataconfirmpostUpload empty');
            } else {
                // $obj = $obj["res"]["info"];
                $obj[0] = $this->postUpload_info($customer_id);
                Log::info('billdataconfirmpostUpload not empty');
            }

            // $obj = json_decode($json, true);
            // $obj = $obj["res"]["info"];
            // foreach($obj as $key => $val) {
            //     $status = false;
            //     $status = $val["status"];
            // }
            // Log::info('billdataconfirmpostUpload  jsonUrl OK');
        } else {
            // echo "データがありません";
            // Log::info('billdataconfirmpostUpload  jsonUrl NG');

        }

        // ログインユーザーのユーザー情報を取得する
        if($status == false) {
            $ret  = $this->postUpload_info($customer_id);

            // Statusを変える
            $status = 99;
            $this->json_put_status($status,$customer_id);
        }

        // * ログインユーザーのCustomerオブジェクトをjsonから取得する
        $compacts = $this->json_get_info($customer_id);

        $config = new FlowConfig();

        // tmpフォルダをユーザーごとに変更
        // $tmp = '/tmp'. '/' . $compacts['u_id'];
        // tmpフォルダをCustomeridごとに変更
        $tmp = '/tmp'. '/billdata' . '/' . $customer_id;

        if(!file_exists( storage_path() . $tmp)){
            mkdir( storage_path() . $tmp, $mode = 0777, true);
        }
        $config->setTempDir(storage_path() . $tmp);
        $config->setDeleteChunksOnSave(false);
        $file = new \Flow\File($config);

        $request = new FlowRequest();

        $totalSize = $request->getTotalSize();

        // アップロード可能なサイズは 30MB
        $maxtataldisp = 30;
        $maxtatalsize = (1024 * 1024 * $maxtataldisp);
        if ($totalSize && $totalSize > $maxtatalsize)
        {
            $errormsg = 'ファイルサイズが大きすぎます。アップロード可能なサイズは '. $maxtataldisp. ' MBまでです。';
            Log::info('billdataconfirmpostUpload  failesize to big ');
            // Statusを変える
            $status = false;
            $this->json_put_status($status,$customer_id);
            //400 Bad Request	一般的なクライアントエラー
            return \Response::json(['error'=>$errormsg,'status'=>'BG'],400);

        }

        $uploadFile = $request->getFile();
     
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($file->checkChunk()) {
                header("HTTP/1.1 200 Ok");
                Log::info('billdataconfirmpostUpload HTTP/1.1 200 Ok ');
            } else {
                //HTTP のレスポンスコード 204 No Content は、リクエストが成功した事を示しますが、
                //クライアントは現在のページから遷移する必要はありません。
                //レスポンスコード 204 が返された場合は、デフォルトでキャッシュ可能になっています。
                //そのようなレスポンスには、 ETag ヘッダーが含まれています。
                header("HTTP/1.1 204 No Content");
                Log::info('billdataconfirmpostUpload HTTP/1.1 204 No Content ');
                return ;
            }
        } else {
            if ($file->validateChunk()) {
                // Log::info('billdataconfirmpostUpload validateChunk ok ');
                $file->saveChunk();
            } else {

                //「400 Bad Request」は、不正な構文、無効なリクエストメッセージフレーミング、
                //または不正なリクエストルーティングのために、サーバーがクライアントによって
                //送信されたリクエストを処理できなかったことを示すHTTPステータスコードです。
                // error, invalid chunk upload request, retry
                header("HTTP/1.1 400 Bad Request");
                Log::debug('billdataconfirmpostUpload HTTP/1.1 400 Bad Request ');
                return ;

                // // strage/tmp
                // $file->deleteChunks();
                // Log::debug('billdataconfirmpostUpload HTTP/1.1 400 Bad Request ');
                // Log::debug('billdataconfirmpostUpload_info  validateChunk not   $uploadFile[name]  = ' . print_r($uploadFile['name'] ,true));

                $errormsg = 'アップロード出来ませんでした。';
                // Indicate that we are not done with all the chunks.
                return \Response::json(['error'=>$errormsg,'status'=>'NG'], 400);
                // return redirect('billdata/index')->with('message', '送信処理出来ませんでした。');
            }
        }

        $fileName = $uploadFile['name'];         // FileName 2023年7月末-20230821T050250Z-001.pdf
        $fileSize = $request->getTotalSize();    // FileSize

        // filedir storageのsave用 /app/public/billdata/folder0171/
        $filedir  = '/app/public/billdata/' . $compacts['foldername'] . '/';
        if(!file_exists( storage_path() . $filedir)){
            mkdir( storage_path() . $filedir, $mode = 0777, true);
        }

        // filepath storageからの取得用 public/billdata/folder0171/   
        $filepath = 'public/billdata/' . $compacts['foldername'] . '/' . $fileName;

        /* アップロードパス */
        // $path =  public/billdata/folder0171/2023年7月末-20230821T050250Z-001.pdf
        $path =  $filedir . $fileName;
        $storage_path = storage_path() . $path;

        Log::info('billdataconfirmpostUpload  $fileName = ' . print_r($fileName,true));
        if ($file->validateFile() && $file->save($storage_path))
        {
            // strage/tmp
            $file->deleteChunks();

            try {
                DB::beginTransaction();
                Log::info('beginTransaction - billdata postUpload saveFile start');

                $billdata = new billdata();
                $billdata->filepath        = $filepath;
                $billdata->filename        = $fileName;
                $billdata->organization_id = $compacts['o_id'];
                $billdata->user_id         = $compacts['u_id'];
                $billdata->customer_id     = $compacts['customer_id'];
                $billdata->filesize        = $fileSize;
                $billdata->urgent_flg      = 2;  // 1:既読 2:未読
                $billdata->save();               //  Inserts

                DB::commit();
                Log::info('beginTransaction - billdata postUpload saveFile end(commit)');
            }
            catch(\QueryException $e) {
                Log::error('exception : ' . $e->getMessage());
                DB::rollback();
                Log::info('beginTransaction - billdata postUpload saveFile end(rollback)');
                // Statusを変える
                $status = false;
                $this->json_put_status($status,$customer_id);
                $errormsg = 'アップロード出来ませんでした。';
                return \Response::json(['error'=>$errormsg,'status'=>'NG'], 400);
            }

            // Statusを変える
            $status = false;
            $this->json_put_status($status,$customer_id);

            Log::info('billdataconfirmpostUpload  END');

            // $data = 'ok';
            // return \Response::json($data, 200);
            return \Response::json(['error'=>'アップロードが正常に終了しました。','status'=>'OK'], 200);
        } else {
            // This is not a final chunk, continue to upload
            Log::info('billdataconfirmpostUpload  This is not a final chunk, continue to upload ');
        }

    }
    /**
     * ログインユーザーのCustomerオブジェクトをSetする
     */
    public function json_put_status($status,$customer_id)
    {
        Log::info('billdataconfirmjson_put_status  START');

        $jsonfile = "";
        $arr = array(
            "res" => array(
                "info" => array(
                    [
                        "status"     => $status
                    ]
                )
            )
        );

        $arr_status = json_encode($arr);
        $jsonfile = storage_path() . "/tmp/billdata_info_status_". $customer_id. ".json";

        file_put_contents($jsonfile , $arr_status);
        Log::info('billdataconfirmjson_put_status  END');
    }

    /**
     * ログインユーザーのCustomerオブジェクトをSetする
     */
    public function json_put_info_set($u_id, $o_id, $customer_id, $foldername, $business_name, $folderpath, $dateNew)
    {
        Log::info('billdataconfirmjson_put_info_set  START');

        $arr = array(
            "res" => array(
                "info" => array(
                    [
                        "u_id"           => $u_id,
                        "o_id"           => $o_id,
                        "customer_id"    => $customer_id,
                        "foldername"     => $foldername,
                        "business_name"  => $business_name,
                        "folderpath"     => $folderpath,
                        "dateNew"        => $dateNew
                    ]
                )
            )
        );

        $arr = json_encode($arr);
        $jsonfile = storage_path() . "/tmp/billdata_info_". $customer_id. ".json";

        file_put_contents($jsonfile , $arr);
        Log::info('billdataconfirmjson_put_info_set  END');
    }

    /**
     * ログインユーザーのCustomerオブジェクトを取得する
     */
    public function json_get_info($customer_id)
    {
        Log::info('billdataconfirmjson_get_info  START');

        $jsonfile = storage_path() . "/tmp/billdata_info_". $customer_id. ".json";

        // Log::debug('billdataconfirmjson_get_info  jsonfile = ' . print_r($jsonfile,true));

        // $jsonUrl = "billdata_info.json"; //JSONファイルの場所とファイル名を記述
        $jsonUrl = $jsonfile; //JSONファイルの場所とファイル名を記述
        if (file_exists($jsonUrl)) {
            $json = file_get_contents($jsonUrl);
            $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
            
            // 2023/09/20
            $obj = [];

            $obj = json_decode($json, true);

            // 2023/09/20
            if(empty($obj)){
                $obj[0] = $this->postUpload_info($customer_id);
                Log::info('billdataconfirmjson_get_info empty');
            } else {
                $obj = $obj["res"]["info"];
                Log::info('billdataconfirmjson_get_info not empty');
            }

            foreach($obj as $key => $val) {
                $u_id          = $val["u_id"];
                $o_id          = $val["o_id"];
                $customer_id   = $val["customer_id"];
                $foldername    = $val["foldername"];
                $business_name = $val["business_name"];
                $folderpath    = $val["folderpath"];
                $dateNew       = $val["dateNew"];
            }
            $check_flg = 1;
            // Log::info('billdataconfirmjson_get_info  OK');
        } else {
            echo "データがありません";
            Log::info('billdataconfirmjson_get_info  NG');
        }
        $compacts = compact( 'u_id','o_id', 'customer_id', 'foldername','business_name','folderpath','dateNew' );

        Log::info('billdataconfirmjson_get_info  END');
        return  $compacts;
    }

    /**
     * Delete uploaded file WEB ROUTE
     * @param Request request
     * @return JsonResponse
     */
    public function delete(Request $request){

        //-------------------------------------------------------------
        //- Request パラメータ
        //-------------------------------------------------------------
        $customer_id = $request->Input('customer_id');

        // ログインユーザーのユーザー情報を取得する
        $user = $this->auth_user_info();
        $u_id = $user->id;

        // 選択された顧客IDからCustomer情報(フォルダー名)を取得する
        $uploadusers     = $this->auth_user_foldername($customer_id);
        $foldername      = $uploadusers->foldername;
        $business_name   = $uploadusers->business_name;
        $filePath        = 'app'. '/' . 'userdata'. '/' . $foldername;

        $file = $request->filename;

        //delete timestamp from filename
        $temp_arr = explode('_', $file);
        if ( isset($temp_arr[0]) ) unset($temp_arr[0]);
        $file = implode('_', $temp_arr);

        $finalPath = storage_path("app/".$filePath);

        if ( unlink($finalPath.$file) ){
        return response()->json([
            'status' => 'ok'
            ], 200);
        }
        else{
        return response()->json([
            'status' => 'error'
            ], 403);
        }
    }

    /**
     * アップロードファイルのバリデート
     * （※本来はFormRequestClassで行うべき）
     *
     * @param Request $request
     * @return Illuminate\Validation\Validator
     */
    private function validateUploadFile(Request $request)
    {
        $rules   = [
            // maxはキロバイト指定になるので、max:1024と指定すると、
            // 1メガバイト以上だとエラーが出る OUTLOOKは20M
            // 300 MB  307200 KB
            // 500 MB  512000 KB
            // 'file'     => 'required|file',
            'file'     => 'required|file|max:512000',
        ];

        $messages = [
            'file.required'  => 'ファイルを選択してください。',
            'file.file'      => 'ファイルアップロード出来ませんでした。',
            'file.max'       => 'ファイルサイズが大きすぎます。'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        return $validator;
    }

}
