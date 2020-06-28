<?php

namespace Greenpacket\Kiple\Tests;

use Greenpacket\Kiple\Kiple;
use Greenpacket\Kiple\Gateways\Gateway;
use Greenpacket\Kiple\Exceptions\InvalidGatewayException;
use Greenpacket\Kiple\Contracts\GatewayApplicationInterface;


class KipleTest extends TestCase
{
  public function testPostGateway()
  {
    $kiple = Kiple::gateway(['foo' => 'bar']);

    $this->assertInstanceOf(Gateway::class, $kiple);
    $this->assertInstanceOf(GatewayApplicationInterface::class, $kiple);
  }

  public function testFooGateway()
  {
    $this->expectException(InvalidGatewayException::class);
    $this->expectExceptionMessage('INVALID_GATEWAY: Gateway [foo] Not Exists');

    Kiple::foo([]);
  }
}
