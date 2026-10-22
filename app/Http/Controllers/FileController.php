<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    public function upload(Request $request){
//        $sub_path = $request->get("type");
//        $this->removeOldUploads();
//
//        $company = \Request::get('company');
//        $file = $request->file;
//        $tmpFilePath = '/uploads/';
//        if(strlen($sub_path)>0){
//            $tmpFilePath = $tmpFilePath."$sub_path/";
//        }
//        $tmpFileName = time() . '_'.$company->id.'.'.$file->getClientOriginalExtension();
//        $file->move(public_path() . $tmpFilePath, $tmpFileName);
//        $path = $tmpFilePath . $tmpFileName;
//
//        $uploaded = new UploadedFile();
//
//        $uploaded->name = $file->getClientOriginalName();
//        $uploaded->path = $path;
//        $uploaded->size = $file->getClientSize();
//        $uploaded->extension = $file->getClientOriginalExtension();
//        $uploaded->url = asset($path);
//        $uploaded->save();
//        return response()->json($uploaded);
    }

    private function removeOldUploads(){
//        $currentDate = Carbon::now();
//        $currentDate->subHour(1);
//        $files = UploadedFile::where([
//            ['created_at', '<=' , $currentDate]
//        ])->whereNull('entity_id')->get();
//
//        foreach($files as $file) try {
//            File::delete(public_path().$file->path);
//        } catch(\Exception $e){
//            error_log($e);
//        }
    }
}
