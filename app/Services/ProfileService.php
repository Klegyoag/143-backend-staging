<?php
namespace App\Services;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class ProfileService
{
 public function handle(string $op,Request $r){$u=$r->user();return match($op){
 'get_me'=>ApiResponse::ok($u),'patch_me'=>$this->patch($r),'post_me_photo'=>$this->photo($r),'delete_me_photo'=>$this->deletePhoto($r),
 'get_me_preferences'=>ApiResponse::ok($u->preferences?:[]),'patch_me_preferences'=>$this->preferences($r),default=>ApiResponse::problem('Unsupported profile operation',$op,501,'not_implemented')};}
 private function patch(Request $r){$d=$r->validate(['display_name'=>'nullable|string|max:100','username'=>['nullable','string','min:3','max:30','regex:/^[A-Za-z0-9._-]+$/','unique:users,username,'.$r->user()->id],'bio'=>'nullable|string|max:1000','timezone'=>'nullable|string|max:80','locale'=>'nullable|string|max:20']);if(isset($d['username']))$d['username']=Str::lower(trim($d['username']));$r->user()->fill($d)->save();return ApiResponse::ok($r->user()->fresh());}
 private function photo(Request $r){$r->validate(['photo'=>'required|image|max:10240']);$path=$r->file('photo')->store('profiles/'.$r->user()->id,config('143.upload_disk'));$r->user()->update(['photo_path'=>$path]);return ApiResponse::created(['path'=>$path]);}
 private function deletePhoto(Request $r){if($r->user()->photo_path)Storage::disk(config('143.upload_disk'))->delete($r->user()->photo_path);$r->user()->update(['photo_path'=>null]);return ApiResponse::ok(['deleted'=>true]);}
 private function preferences(Request $r){$d=$r->validate(['preferences'=>'required|array']);$r->user()->update(['preferences'=>array_replace_recursive($r->user()->preferences?:[],$d['preferences'])]);return ApiResponse::ok($r->user()->preferences);}
}
