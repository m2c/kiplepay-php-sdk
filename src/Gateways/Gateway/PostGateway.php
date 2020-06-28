<?php

namespace Greenpacket\Kiple\Gateways\Gateway;

use Greenpacket\Kiple\Events;
use Greenpacket\Kiple\Supports\Collection;
use Symfony\Component\HttpFoundation\Response;
use Greenpacket\Kiple\Contracts\GatewayInterface;
use Greenpacket\Kiple\Exceptions\BusinessException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Greenpacket\Kiple\Exceptions\InvalidConfigException;

class PostGateway extends KipleGateway
{
  /**
   * the post gateway.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @param string $endpoint
   * @param array  $payload
   *
   * @throws InvalidConfigException
   *
   * @return Collection
   */
  public function gateway($endpoint, array $payload): Collection
  {
    $biz_array = json_decode($payload['biz_content'], true);

    if(isset($biz_array['request_uri']) && $biz_array['request_uri']){
      $endpoint = $endpoint.$biz_array['request_uri'];
      unset($biz_array['request_uri']);
    }else{
      throw new BusinessException("Missing the request uri");
    }
    $payload['biz_content'] = json_encode($biz_array);
    
    $payload['sign'] = Support::generateSign($payload);
    
    Events::dispatch(new Events\RequestStarted('Post', $endpoint, $payload));
    
    $endpoint = Support::buildUrlEncode("post",$endpoint,$payload);
    return Support::requestApi('post',$endpoint,$payload);
  }
}
