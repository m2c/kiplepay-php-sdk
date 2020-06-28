<?php

namespace Greenpacket\Kiple\Gateways;

use Greenpacket\Kiple\Events;
use Greenpacket\Kiple\Supports\Str;
use Greenpacket\Kiple\Supports\Config;
use Greenpacket\Kiple\Supports\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Greenpacket\Kiple\Gateways\Gateway\Support;
use Greenpacket\Kiple\Contracts\GatewayInterface;
use Greenpacket\Kiple\Exceptions\GatewayException;
use Greenpacket\Kiple\Exceptions\InvalidSignException;
use Greenpacket\Kiple\Exceptions\InvalidConfigException;
use Greenpacket\Kiple\Exceptions\InvalidGatewayException;
use Greenpacket\Kiple\Exceptions\InvalidArgumentException;
use Greenpacket\Kiple\Contracts\GatewayApplicationInterface;


/**
 * @method Collection   get(array $config)      the get gateway
 * @method Collection post(array $config)       the post gateway 
 * @method Collection verfiy(array $config)     the return verify
 */
class Gateway implements GatewayApplicationInterface
{
  /**
   * the payload.
   *
   * @var array
   */
  protected $payload;

  /**
   * the gateway.
   *
   * @var string
   */
  protected $gateway;

  /**
   * extends.
   *
   * @var array
   */
  protected $extends;

  /**
   * Bootstrap.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @throws \Exception
   */
  public function __construct(Config $config)
  {
    $this->gateway = Support::create($config)->getBaseUri();
    $this->payload = [
      'app_id'         => $config->get('app_id'),
      'format'         => 'JSON',
      'charset'        => 'UTF-8',
      'sign_type'      => 'RSA2',
      'version'        => '1.0.0',
      'return_url'     => $config->get('return_url'),
      'notify_url'     => $config->get('notify_url'),
      'timestamp'      => Support::getMillisecond(),
      'sign'           => '',
      'biz_content'    => '',
    ];
  }

  /**
   * Magic gateway.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @param string $method
   * @param array  $params
   *
   * @throws GatewayException
   * @throws InvalidArgumentException
   * @throws InvalidConfigException
   * @throws InvalidGatewayException
   * @throws InvalidSignException
   *
   * @return Response|Collection
   */
  public function __call($method, $params)
  {
    // if (isset($this->extends[$method])) {
    //   return $this->makeExtend($method, ...$params);
    // }

    return $this->gateway($method, ...$params);
  }

  /**
   * gateway.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @param string $gateway
   * @param array  $params
   *
   * @throws InvalidGatewayException
   *
   * @return Response|Collection
   */
  public function gateway($gateway, $params = [])
  {
    Events::dispatch(new Events\RequestStarting($gateway, $params));

    $this->payload['return_url'] = $params['return_url'] ?? $this->payload['return_url'];
    $this->payload['notify_url'] = $params['notify_url'] ?? $this->payload['notify_url'];

    unset($params['return_url'], $params['notify_url']);

    $this->payload['biz_content'] = json_encode($params);

    $gateway = get_class($this).'\\'.Str::studly($gateway).'Gateway';

    if (class_exists($gateway)) {
      return $this->makeGateway($gateway);
    }

    throw new InvalidGatewayException("Pay Gateway [{$gateway}] not exists");
  }

  /**
   * Verify sign.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @param array|null $data
   *
   * @throws InvalidSignException
   * @throws InvalidConfigException
   */
  public function verify($data = null, bool $refund = false): Collection
  {
    if (is_null($data)) {
      $request = Request::createFromGlobals();

      $data = $request->request->count() > 0 ? $request->request->all() : $request->query->all();
    }

    if (isset($data['fund_bill_list'])) {
      $data['fund_bill_list'] = htmlspecialchars_decode($data['fund_bill_list']);
    }

    Events::dispatch(new Events\RequestReceived('', $data));

    if (Support::verifySign($data)) {
      return new Collection($data);
    }

    Events::dispatch(new Events\SignFailed('', $data));

    throw new InvalidSignException('Sign Verify FAILED', $data);
  }

  /**
   * Make the gateway.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @throws InvalidGatewayException
   *
   * @return Response|Collection
   */
  protected function makeGateway(string $gateway)
  {
    $app = new $gateway();

    if ($app instanceof GatewayInterface) {
      return $app->gateway($this->gateway, array_filter($this->payload, function ($value) {
        return '' !== $value && !is_null($value);
      }));
    }

    throw new InvalidGatewayException("The Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
  }

  /**
   * extend.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @throws GatewayException
   * @throws InvalidConfigException
   * @throws InvalidSignException
   * @throws InvalidArgumentException
   */
  public function extend(string $method, callable $function, bool $now = true): ?Collection
  {
    if (!$now && !method_exists($this, $method)) {
      $this->extends[$method] = $function;
      return null;
    }

    $customize = $function($this->payload);

    if (!is_array($customize) && !($customize instanceof Collection)) {
      throw new InvalidArgumentException('Return Type Must Be Array Or Collection');
    }

    Events::dispatch(new Events\MethodCalled('extend', $this->gateway, $customize));

    if (is_array($customize)) {
      $this->payload = $customize;
      $this->payload['sign'] = Support::generateSign($this->payload);

      return Support::requestApi($this->payload);
    }

    return $customize;
  }

  /**
   * makeExtend.
   *
   * @author Evans <evans.yang@greenpacket.com.cn>
   *
   * @throws GatewayException
   * @throws InvalidArgumentException
   * @throws InvalidConfigException
   * @throws InvalidSignException
   */
  protected function makeExtend(string $method, array ...$params): Collection
  {
    $params = count($params) >= 1 ? $params[0] : $params;

    $function = $this->extends[$method];

    $customize = $function($this->payload, $params);

    if (!is_array($customize) && !($customize instanceof Collection)) {
      throw new InvalidArgumentException('Return Type Must Be Array Or Collection');
    }

    Events::dispatch(new Events\MethodCalled(
      'extend - '.$method,
      $this->gateway,
      is_array($customize) ? $customize : $customize->toArray()
    ));

    if (is_array($customize)) {
      $this->payload = $customize;
      $this->payload['sign'] = Support::generateSign($this->payload);

      return Support::requestApi($this->payload);
    }

    return $customize;
  }
}
