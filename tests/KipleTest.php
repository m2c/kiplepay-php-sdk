<?php

namespace Greenpacket\KiplePay\Tests;

use Greenpacket\KiplePay\Kiple;
use Greenpacket\KiplePay\Gateways\Gateway;
use Greenpacket\KiplePay\Exceptions\InvalidGatewayException;
use Greenpacket\KiplePay\Contracts\GatewayApplicationInterface;


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
