<?php

namespace App\Entity;

//use App\Domain\Order\Repository\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
class OrderItem
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column(type: Types::INTEGER)]
  private ?int $id = null;

  #[ORM\ManyToOne]
  #[ORM\JoinColumn(nullable: false)]
  private ?Order $orderRef = null;

  #[ORM\Column(type: Types::INTEGER)]
  #[Groups(['order:read'])]
  private ?int $productId = null;

  #[ORM\Column(type: Types::STRING, length: 255)]
  #[Groups(['order:read'])]
  private ?string $productName = null;

  #[ORM\Column(type: Types::FLOAT)]
  #[Groups(['order:read'])]
  private ?float $productPrice = null;

  #[ORM\Column(type: Types::INTEGER)]
  #[Groups(['order:read'])]
  private ?int $quantity = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getOrderRef(): ?Order
  {
    return $this->orderRef;
  }

  public function setOrderRef(?Order $orderId): static
  {
    $this->orderRef = $orderId;

    return $this;
  }

  public function getInteger(): ?string
  {
    return $this->integer;
  }

  public function setInteger(string $integer): static
  {
    $this->integer = $integer;

    return $this;
  }

  public function getProductId(): ?int
  {
    return $this->productId;
  }

  public function setProductId(int $productId): static
  {
    $this->productId = $productId;

    return $this;
  }

  public function getProductName(): ?string
  {
    return $this->productName;
  }

  public function setProductName(string $productName): static
  {
    $this->productName = $productName;

    return $this;
  }

  public function getProductPrice(): ?float
  {
    return $this->productPrice;
  }

  public function setProductPrice(float $productPrice): static
  {
    $this->productPrice = $productPrice;

    return $this;
  }

  public function getQuantity(): ?int
  {
    return $this->quantity;
  }

  public function setQuantity(int $quantity): static
  {
    $this->quantity = $quantity;

    return $this;
  }
}
