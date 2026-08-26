<?php
namespace Tests\Feature;
use Tests\TestCase;
class HealthTest extends TestCase
{
 public function test_framework_health_route_responds():void{$this->get('/up')->assertSuccessful();}
}
