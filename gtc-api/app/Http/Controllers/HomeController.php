<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MonthlyPayroll;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Staff;
use Illuminate\Support\Facades\File;
use ZipArchive;

class HomeController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public static function getTotal($id)
    {
        $revenue = Receipt::all();
        $value = $revenue->sum('amount');
        return $value;  
    }

    public function download()
    {
        // Backup the database
        $filename = 'database_backup_' . date('YmdHis') . '.sql';
        $path = storage_path('app/public/' . $filename);
    
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.host'),
            config('database.connections.mysql.database'),
            $path
        );
    
        exec($command);
    
        // Zip the backup file
        $zipFilename = 'database_backup_' . date('YmdHis') . '.zip';
        $zipPath = storage_path('app/public/' . $zipFilename);
    
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
            $zip->addFile($path, $filename);
            $zip->close();
    
            // Delete the unzipped backup file
            File::delete($path);
    
            // Stream the zipped backup file for download
            return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
        } else {
            // If unable to create the zip, return an error response
            return response()->json(['error' => 'Failed to create the database backup zip.'], 500);
        }
    }
    
    public function index()
    {
        $data['users']= User::count();
        $data['staff']= Staff::count();
        //pick the latest monthly payroll
        $latestPayroll= MonthlyPayroll::latest()->first();
        //use it to get other data
        $data['monthlyPayroll']= MonthlyPayroll::where('month', $latestPayroll->month)
        ->where('year', $latestPayroll->year)->get();
        $data['staff']= Staff::count();
        return view('admin.home');
    }
}
