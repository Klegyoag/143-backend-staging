<?php
namespace App\Support;
use App\Models\CoupleMember;
use Illuminate\Http\Request;
class RequestContext
{
    public static function coupleSpaceId(Request $request): ?string
    {
        $user=$request->user(); if(!$user) return null;
        return CoupleMember::where('user_id',$user->id)->value('couple_space_id');
    }
    public static function routeId(Request $request): ?string
    {
        foreach ($request->route()->parameters() as $key=>$value) if ($key!=='token' && is_string($value) && preg_match('/Id$/',$key)) return $value;
        return null;
    }
}
