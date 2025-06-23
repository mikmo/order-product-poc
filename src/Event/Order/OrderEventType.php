<?php
// src/Event/Order/OrderEventType.php
namespace App\Event\Order;

final class OrderEventType
{
  public const CREATED = 'created';
  public const UPDATED = 'updated';
  public const DELETED = 'deleted';

  // Preveniamo l'istanziazione della classe
  private function __construct() {}

  /**
   * @return array<string>
   */
  public static function getAllTypes(): array
  {
    return [
      self::CREATED,
      self::UPDATED,
      self::DELETED
    ];
  }

  public static function isValid(string $type): bool
  {
    return in_array($type, self::getAllTypes());
  }
}
