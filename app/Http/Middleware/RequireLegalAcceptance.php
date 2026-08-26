<?php
namespace App\Http\Middleware;
use Closure;
use App\Models\LegalDocument;
use App\Models\UserConsent;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class RequireLegalAcceptance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user=$request->user();
        if (! $user) return $next($request);
        $required=config('143.legal.required_types', ['terms','privacy']);
        foreach ($required as $type) {
            $doc=LegalDocument::where('type',$type)->where('is_active',true)->latest('effective_at')->first();
            if ($doc && !UserConsent::where('user_id',$user->id)->where('legal_document_id',$doc->id)->whereNotNull('accepted_at')->exists()) {
                return ApiResponse::problem('Legal acceptance required','Accept the current Terms & Conditions and Privacy Policy before using protected features.',428,'legal_acceptance_required',['required_document'=>['id'=>$doc->id,'type'=>$doc->type,'version'=>$doc->version]]);
            }
        }
        return $next($request);
    }
}
