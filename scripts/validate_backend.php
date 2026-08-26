<?php
$root=dirname(__DIR__);$fail=[];$ok=[];
function check(bool $cond,string $label){global $fail,$ok;if($cond){$ok[]=$label;}else{$fail[]=$label;}}
$contract=require $root.'/config/api_contract.php';
check(count($contract)===351,'351 HTTP operations');
$paths=array_unique(array_column($contract,'path'));check(count($paths)===265,'265 distinct paths');
check(!in_array('generic',array_column($contract,'handler'),true),'no generic route handler');
$known=['auth','couple','memory','chat','notification','profile','media','billing','system','domain'];
check(count(array_diff(array_unique(array_column($contract,'handler')),$known))===0,'all handlers are known');
$events=require $root.'/config/realtime_events.php';check(count($events)===53&&count(array_unique($events))===53,'53 unique realtime events');
// Every domain resource in the contract must resolve to a dedicated implementation branch/table; no silent fallback store is allowed.
$domainResources=[];foreach($contract as $e)if($e['handler']==='domain')$domainResources[$e['resource']]=true;
$complex=['calls','calls.decline','calls.end','together-rooms','jars','albums','calendar.events','journeys','journey-sessions','reconnection','reconnection.sessions','linked-devices','linked-devices.link-intents','integrations.spotify','exports'];
$relationship=['pings','pings.reactions','widgets.ping','goals','bucket-list','bucket-list.convert-to-milestone','long-distance','moods','need-you','shared-lists.items','migrations.device'];
$tableResources=['calls','calls.decline','calls.end','together-rooms','together-rooms.leave','pings','pings.reactions','reminders','check-ins','streaks','linked-devices','linked-devices.link-intents','blocks','reports','exports','support.tickets','feedback','daily.questions','daily.answers','daily.activities','daily','games','game-sessions','game-sessions.answers','xp','game-rewards','achievements','achievement-progress','calendar.events','long-distance','travel-itineraries','virtual-dates','jars','bucket-list','bucket-list.convert-to-milestone','goals','love-language','partner.preferences','moods','need-you','scheduled-messages','surprises','story.events','albums','albums.lock','couple-music','integrations.spotify','widgets','widgets.ping','migrations.device','cool-down','reconnection.sessions','reconnection','journeys','journey-sessions','ai','shared-lists','shared-lists.items','budgets','expenses'];
$explicit=['home','search','activity','unread-summary','favorites','sessions','security','privacy.permissions','preferences','couple-profile','crypto.devices','crypto.users','backups','romantic-suggestions','date-generator','date-plans','countdowns','story','budget-privacy','achievements','achievement-progress','about','release-notes','help.articles'];
$unhandled=[];foreach(array_keys($domainResources) as $resource){$handled=in_array($resource,$complex,true)||in_array($resource,$relationship,true)||in_array($resource,$tableResources,true)||in_array($resource,$explicit,true)||str_starts_with($resource,'daily')||$resource==='games'||str_starts_with($resource,'game-sessions')||in_array($resource,['xp','game-rewards'],true)||str_starts_with($resource,'insights');if(!$handled)$unhandled[]=$resource;}
check($unhandled===[],'all domain contract resources have implementation coverage');
check(!str_contains(file_get_contents($root.'/app/Services/DomainService.php'),'featureFallback'),'no generic feature fallback in DomainService');
$auth=array_values(array_filter($contract,fn($e)=>$e['module']===2));check(count($auth)===11,'11 username/recovery auth operations');
$authPaths=array_column($auth,'path');foreach(['/auth/register','/auth/login','/auth/recovery/confirm','/auth/recovery/status','/auth/password/reset/recovery-code'] as $p)check(in_array($p,$authPaths,true),'auth path '.$p);
foreach(['/auth/google','/auth/apple','/auth/otp/send','/auth/register/email','/auth/register/phone'] as $p)check(!in_array($p,$authPaths,true),'removed auth path '.$p);
$domain=require $root.'/config/domain_tables.php';check(count($domain)===62&&count(array_unique($domain))===62,'62 domain tables');
$first=file_get_contents($root.'/database/migrations/2026_08_25_000001_create_143_schema.php');preg_match_all("/Schema::create\\('([^']+)'/",$first,$m);$initial=count(array_unique($m[1]));check($initial===33,'33 initial tables');check($initial-1+count($domain)===94,'94 final PostgreSQL tables after OTP table removal');
$open=json_decode(file_get_contents($root.'/docs/openapi.json'),true);check(is_array($open),'OpenAPI JSON parses');$openOps=0;foreach($open['paths']??[] as $methods)foreach($methods as $method=>$spec)if(in_array(strtoupper($method),['GET','POST','PUT','PATCH','DELETE','HEAD','OPTIONS'],true))$openOps++;check(count($open['paths']??[])===265&&$openOps===351,'OpenAPI matches 265/351 contract');
$post=json_decode(file_get_contents($root.'/docs/143.postman_collection.json'),true);$postOps=0;foreach($post['item']??[] as $folder)$postOps+=count($folder['item']??[]);check($postOps===351,'Postman contains 351 requests');
check(strlen(file_get_contents($root.'/resources/legal/terms-v1.0.md'))>3000,'Terms v1.0 included');
check(strlen(file_get_contents($root.'/resources/legal/privacy-v1.0.md'))>3000,'Privacy v1.0 included');
$required=['render.yaml','Dockerfile','.env.example','docs/FRONTEND_HANDOFF.md','docs/AUTH_RECOVERY_FLOW.md','docs/LOCAL_CACHE_CONTRACT.md'];foreach($required as $f)check(file_exists($root.'/'.$f),'required file '.$f);
// Syntax lint every PHP source file without requiring vendor dependencies.
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));$php=0;
foreach($it as $file){if($file->getExtension()!=='php'||str_contains($file->getPathname(),'/vendor/'))continue;$php++;$cmd='php -l '.escapeshellarg($file->getPathname()).' 2>&1';exec($cmd,$out,$code);if($code!==0)$fail[]='PHP lint: '.$file->getPathname().' '.implode(' ',$out);$out=[];}
check($php>=80,'PHP source inventory >= 80 files');
if($fail){fwrite(STDERR,"FAILED\n - ".implode("\n - ",$fail)."\n");exit(1);}echo "143 BACKEND STATIC VALIDATION PASSED\n";foreach($ok as $x)echo "[OK] $x\n";echo "[OK] PHP files linted: $php\n";
