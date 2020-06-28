<?php

namespace Greenpacket\Kiple\Contracts;

use Symfony\Component\HttpFoundation\Response;
use Greenpacket\Kiple\Supports\Collection;

interface GatewayInterface
{
  /**
   * gateway.
   *
   * @author Evasn <evans.yang@greenpacket.com.cn>
   *
   * @param string $endpoint
   *
   * @return Collection|Response
   */
  public function gateway($endpoint, array $payload);
}
