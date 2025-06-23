<?php
// src/Message/OrderIndexMessage.php
namespace App\Message;

class OrderIndexMessage
{
  private int $orderId;
  private string $action;

  public function __construct(int $orderId, string $action)
  {
    $this->orderId = $orderId;
    $this->action = $action; // 'created', 'updated', 'deleted'
  }

  public function getOrderId(): int
  {
    return $this->orderId;
  }

  public function getAction(): string
  {
    return $this->action;
  }
}
