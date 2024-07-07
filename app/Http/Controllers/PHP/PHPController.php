<?php

namespace App\Http\Controllers\PHP;


use App\Http\Controllers\Controller;
use App\Models\NodeJS\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Models\PHP\Topic;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Session;

class PHPController extends Controller{
    public function index(){
        
        $actual = "";
        $topics = Topic::all();
        $topicsCount = count($topics);
       
        return view('php.student.material.index',[
            'result_up'     => $actual,
            'topics'        => $topics,
            'topicsCount'   => $topicsCount,
        ]);
    }

    function php_material_detail(){
        $phpid  = isset($_GET['phpid']) ? (int)$_GET['phpid'] : '';
        $start  = isset($_GET['start']) ? (int)$_GET['start'] : '';
        $output = isset($_GET['output']) ? $_GET['output'] : '';
        // dd($start);
      
        $results = DB::select("select * from php_topics_detail where  id_topics = $phpid and id ='$start' ");
        foreach($results as $r){
            
            if( $phpid == $r->id_topics){
                if(empty($r->file_name)){
                     $contain = $r->description;
                     $pdf_reader = 0;
                }else{
                    $contain = $r->file_name;
                    $pdf_reader = 1;
                }
               
                $html_start = $this->html_persyaratan($contain,$pdf_reader);
            }else{
                $html_start = "";
                
            }
        }


        $listTask = DB::select("select aa.*, us.name from php_user_submits aa join users us on aa.userid = us.id where php_id = $phpid and php_id_topic = $start ");
        
        $idUser         = Auth::user()->id;
        $roleTeacher    = DB::select("select role from users where id = $idUser");

        $topics = Topic::all();
        $detail = Topic::findorfail($phpid);
        $topicsCount = count($topics);
        $detailCount = ($topicsCount/$topicsCount)*10;
        return view('php.student.material.topics_detail',[
            'row'        => $detail,
            'topics'     => $topics,
            'phpid'      => $phpid,
            'html_start' => $html_start,
            'pdf_reader' => $pdf_reader ,
            'topicsCount'=> $topicsCount ,
            'detailCount'=> $detailCount ,
            'output'     => $output,
            'flag'       => $results[0] ? $results[0]->flag : 0,
            'listTask'   => $listTask,
            'role'       => $roleTeacher[0] ? $roleTeacher[0]->role : '',
        ]);
    }
    function html_start(){
        $html ="<div style='text-align:center;font-size:18px'><em>Modul kelas Belajar Pengembangan Aplikasi Android Intermediate dalam bentuk cetak (buku) maupun elektronik sudah didaftarkan ke Dirjen HKI, Kemenkumham RI. Segala bentuk penggandaan dan atau komersialisasi, sebagian atau seluruh bagian, baik cetak maupun elektronik terhadap modul kelas <em>Belajar Pengembangan Aplikasi Android Intermediate</em> tanpa izin formal tertulis kepada pemilik hak cipta akan diproses melalui jalur hukum.</em></div>";
        return $html;
    }
    function html_persyaratan($desc,$pdf_reader){
        $html = $desc;
        return $html;
    }

    public function upload(Request $request)
    {
        if ($request->hasFile('upload')) {
            $originName = $request->file('upload')->getClientOriginalName();
            $fileName = pathinfo($originName, PATHINFO_FILENAME);
            $extension = $request->file('upload')->getClientOriginalExtension();
            $fileName = $fileName . '_' . time() . '.' . $extension;

            $request->file('upload')->move(public_path('media'), $fileName);

            $url = asset('media/' . $fileName);

            return response()->json(['fileName' => $fileName, 'uploaded' => 1, 'url' => $url]);
        }
    
    }

    function send_task(){
        $phpid  = isset($_GET['phpid']) ? $_GET['phpid'] : '';
        $task   = isset($_GET['task']) ? $_GET['task'] : '';
        $output = isset($_GET['output']) ? $_GET['output'] : '';
        
        $task_db = DB::table('php_task')
                                        ->where('id_topics', $phpid)
                                        ->where('id', $task)
                                        ->first();

        if($task_db->id == $task){
            $html_start = $this->html_task();
        }else{
            $html_start = "";
        }
        
        $pdf_reader = 0;
        $topics = Topic::all();
        $detail = Topic::findorfail($phpid);
        $topicsCount = count($topics);
        $persen = ($topicsCount/$topicsCount)*10;
        session(['params' => $persen]);

        return view('php.student.material.topics_detail',[
            'row'        => $detail,
            'topics'     => $topics,
            'phpid'      => $phpid,
            'html_start' => $html_start,
            'pdf_reader' => $pdf_reader ,
            'detailCount'=> $persen,
            'output'     => $output,
        ]);

    }
    function html_task(){
        return view('php.student.task.form_submission_task',[]);
    }
    function php_admin(){
        return view('php.admin.material.upload_materi',[]);

    }

    function task_submission(Request $request){
        $phpid = (int)$request->get('phpid');
        $start = (int)$request->get('start');

        $this->validate($request, [
            'file' => 'required',
           
        ]);
     
        // menyimpan data file yang diupload ke variabel $file
        $file = $request->file('file');
     
        $file_name = Auth::user()->name.'_'.$file->getClientOriginalName();
       
        Storage::disk('public')->makeDirectory('private/'.Auth::user()->name);
        Storage::disk('public')->put('/private/'.Auth::user()->name.'/'.$file_name,File::get($file));
        $userName = Auth::user()->name;
        Session::put('user_name', $userName);
        $user_name = Session::get('user_name');
        $name = Session::put('ori_file_name', $file_name);

        $path = storage_path("app/private/{$userName}/{$file_name}");
        Session::put('path', $path);

        $val = session('key');
        // DB::select("TRUNCATE TABLE php_user_submits");
        // DB::insert("insert into php_user_submits(userid) values ('$val')");

        $phpunitExecutable  = base_path('vendor/bin/phpunit');

        Storage::disk('local')->put('/private/testingunit/testingunit.php',File::get($file));
        if($start == 43){
            $unitTest           = base_path('tests/CreateDatabase.php');
        }else if($start == 42){
            $unitTest           = base_path('tests/CheckConnection.php');
        }else if($start == 44){
            $unitTest           = base_path('tests/CreateTable.php');
        }else if($start == 45){
            $unitTest           = base_path('tests/CreateTableGuru.php');
        }else if($start == 46){
            $unitTest           = base_path('tests/CheckInsert.php');
        }else if($start == 47){
            $unitTest           = base_path('tests/CheckInsertGuru.php');
        }else if($start == 48){
            $unitTest           = base_path('tests/CheckInsertHtml.php');
        }else if($start == 49){
            $unitTest           = base_path('tests/CheckInsertHtmlGuru.php');
        }else if($start == 50){
            $unitTest           = base_path('tests/CheckSelectHtml.php');
        }else if($start == 51){
            $unitTest           = base_path('tests/CheckSelectHtmlGuru.php');
        }else if($start == 52){
            $unitTest           = base_path('tests/CheckUpdateHtml.php');
        }else if($start == 53){
            $unitTest           = base_path('tests/CheckUpdateHtmlGuru.php');
        }else if($start == 54){
            $unitTest           = base_path('tests/CheckDeleteHtml.php');
        }else if($start == 55){
            $unitTest           = base_path('tests/CheckDeleteHtmlGuru.php');
        }

        // Run PHPUnit tests using exec
        $output = [];
        $returnVar = 0;

        exec("$phpunitExecutable $unitTest", $output, $returnVar);
        Storage::deleteDirectory('/private/testingunit');

        // Output the results
        $outputString  = "<br>PHPUnit Output: <br>";
        $outputString .= implode("<br>", $output) . "<br>";
        $outputString .= "Return Code: $returnVar<br>";
        // dd($output);

        $idUser     = Auth::user()->id;
        $pathuser   = 'storage/private/'.$userName.'/'.$file_name.'';   

        $flag       = $returnVar == 0 ? 'true' : 'false'; 

        DB::insert("INSERT INTO php_user_submits(userid, path, flag, php_id, php_id_topic) values ('$idUser', '$pathuser', '$flag', $phpid, $start)");

        // php_user_submits
        return redirect('/php/detail-topics?phpid='.$phpid.'&start='.$start.'&output='.$outputString.'');

    }
    
    function unittesting2(){
        $val = session('key');
        DB::select("TRUNCATE TABLE php_user_submits");
        DB::insert("insert into php_user_submits(userid) values ('$val')");

        $path_test = base_path("phpunit.xml");
        $path = base_path("vendor\bin\phpunit -c $path_test");
        $output = shell_exec($path);
        
        // echo dd($output);
        // echo json_encode($output);
        $string  = htmlentities($output);
        $string = str_replace("\n", ' ', $string);

        $pattern = '/PHPUnit\s+(\d+\.\d+\.\d+).*Runtime:\s+PHP\s+(\d+\.\d+\.\d+).*Time:\s+(\d+:\d+\.\d+),\s+Memory:\s+(\d+\.\d+)\s+MB\s+OK\s+\((\d+)\stests,\s+(\d+)\sassertions\)/';

        if (preg_match($pattern, $string, $matches)) {
            $phpUnitVersion  = $matches[1];
            $phpVersion      = $matches[2];
            $executionTime   = $matches[3];
            $memoryUsage     = $matches[4];
            $numTests        = $matches[5];
            $numAssertions   = $matches[6];
        
            // Output the extracted information
            echo "PHPUnit version: $phpUnitVersion <br />";
            echo "PHP version: $phpVersion <br />";
            echo "Execution time: $executionTime <br />";
            echo "Memory usage: $memoryUsage MB <br />";
            echo "Number of tests: $numTests <br />";
            echo "Number of assertions: $numAssertions <br />";
            
            $ok_position = strpos($string, 'OK');
            if ($ok_position !== false) {
                $ok_part = substr($string, $ok_position);
                echo "Tests Run : ". $ok_part;
            }

        }else{
            
            $string = json_encode($output);
            $text = str_replace("\n", ' ', $output);
            // Define patterns to extract relevant information
            $pattern_phpunit_version = '/PHPUnit\s+(\d+\.\d+\.\d+)/';
            $pattern_php_runtime = '/Runtime:\s+PHP\s+([\d.]+)/';
            $pattern_configuration = '/Configuration:\s+(.+)/';
            $pattern_failure_count = '/There was (\d+) failure/';
            $pattern_failure_test_case = '/Failed asserting that \'(.*?)\' contains \'(.*?)\'./';
            $pattern_failure_location = '/(C:\\\\.*?\\.php):(\d+)/';

            // Perform matching
            preg_match($pattern_phpunit_version, $text, $matches_phpunit_version);
            preg_match($pattern_php_runtime, $text, $matches_php_runtime);
            preg_match($pattern_configuration, $text, $matches_configuration);
            preg_match($pattern_failure_count, $text, $matches_failure_count);
            preg_match($pattern_failure_test_case, $text, $matches_failure_test_case);
            preg_match($pattern_failure_location, $text, $matches_failure_location);

            // Extracted information
            $phpunit_version = isset($matches_phpunit_version[1]) ? $matches_phpunit_version[1] : "Not found";
            $php_runtime = isset($matches_php_runtime[1]) ? $matches_php_runtime[1] : "Not found";
            $configuration_path = isset($matches_configuration[1]) ? $matches_configuration[1] : "Not found";
            $num_failures = isset($matches_failure_count[1]) ? $matches_failure_count[1] : "Not found";
            $failed_assertion = isset($matches_failure_test_case[1]) ? htmlspecialchars($matches_failure_test_case[1]) : "Not found";
            $expected_content = isset($matches_failure_test_case[2]) ? htmlspecialchars($matches_failure_test_case[2]) : "Not found";
            $failure_location = isset($matches_failure_location[1]) ? $matches_failure_location[1] : "Not found";
            $failure_line = isset($matches_failure_location[2]) ? $matches_failure_location[2] : "Not found";

            // Output extracted information
            echo "PHPUnit version: $phpunit_version <br >";
            echo "PHP Runtime: $php_runtime <br >";
            echo "Configuration path: $configuration_path <br >";
            echo "Number of failures: $num_failures <br >";
            echo "Failed assertion: $failed_assertion <br >";
            echo "Expected content: $expected_content <br >";
            echo "Failure location: $failure_location <br >";
            echo "Failure line: $failure_line <br >";                
        }
                 
    }

    function unittesting(){
        $namaFile = 'febri syawaldi_CreateDB.php';
        $phpunitExecutable  = base_path('vendor/bin/phpunit');
        $unitTest           = base_path('tests/FileReadTest.php');

        // Run PHPUnit tests using exec
        $output = [];
        $returnVar = 0;
        exec("$phpunitExecutable $unitTest", $output, $returnVar);


        // Output the results
        // echo "PHPUnit Output: <br>";
        // echo implode("<br>", $output) . "<br>";
        // echo "Return Code: $returnVar<br>";

        return response()->json($output);
    }
    
    function session_progress(){
        session(['params' => $_POST['params']]);
    }
}