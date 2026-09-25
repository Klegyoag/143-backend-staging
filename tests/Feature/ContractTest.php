<?php
namespace Tests\Feature;
use Tests\TestCase;
class ContractTest extends TestCase
{
    public function test_contract_counts_and_auth_override(): void
    {
        $contract=config('api_contract');
        $this->assertCount(356,$contract);
        $this->assertCount(270,array_unique(array_column($contract,'path')));
        $this->assertNotContains('generic',array_column($contract,'handler'));
        $auth=array_values(array_filter($contract,fn($e)=>$e['module']===2));
        $this->assertCount(11,$auth);
        $paths=array_column($auth,'path');
        $this->assertContains('/auth/register',$paths);
        $this->assertContains('/auth/login',$paths);
        $this->assertContains('/auth/recovery/confirm',$paths);
        $this->assertNotContains('/auth/google',$paths);
        $this->assertNotContains('/auth/apple',$paths);
        $this->assertNotContains('/auth/otp/send',$paths);
    }
    public function test_realtime_contract_has_53_unique_events():void
    {
        $events=config('realtime_events');$this->assertCount(53,$events);$this->assertCount(53,array_unique($events));
    }
}
