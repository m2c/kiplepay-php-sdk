<?php

namespace Greenpacket\Kiple\Gateways\Gateway;

use Greenpacket\Kiple\Supports\Collection;
use Greenpacket\Kiple\Contracts\GatewayInterface;
use Greenpacket\Kiple\Exceptions\InvalidArgumentException;

abstract class KipleGateway implements GatewayInterface
{

  /**
   * Bootstrap.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @throws InvalidArgumentException
   */
  public function __construct()
  {
  }

  /**
   * Pay an order.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @param string $endpoint
   *
   * @return Collection
   */
  abstract public function gateway($endpoint, array $payload);
}
