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

    if(isset($biz_array['api_url']) && $biz_array['api_url']){
      $endpoint = $endpoint.$biz_array['api_url'];
      unset($biz_array['api_url']);
    }else{
      throw new BusinessException("Missing the request api url");
    }

    $payload['biz_content'] = json_encode($biz_array['biz_content']);

    $payload['sign'] = Support::generateSign($payload);
    
    Events::dispatch(new Events\RequestStarted('Post', $endpoint, $payload));
    
    $endpoint = Support::buildUrlEncode("post",$endpoint,$payload);

    return Support::requestApi('post',$endpoint,$payload);
  }
}
