<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: 'App\Repository\OrderRepository')]
#[ORM\Table(name: '`order`')]
class Order
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column(type: Types::INTEGER)]
  #[Groups(['order:read','order:create', 'order:update'])]
  private int $id;

  #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
  #[Groups(['order:read'])]
  private ?string $name = null;

  #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
  #[Groups(['order:read'])]
  private ?string $description = null;

  #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
  #[Groups(['order:read'])]
  private \DateTimeImmutable $date;


  #[ORM\Column(type: Types::INTEGER)]
  #[ORM\Version]
  #[Groups(['order:read','order:update'])]
  private int $version;

  /**
   * @var Collection<int, OrderItem>
   */
  #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class)]
  #[Groups(['order:read'])]
  private Collection $items;


  public function __construct()
  {
    $this->items = new ArrayCollection();
    $this->date = new \DateTimeImmutable();
  }

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getName(): ?string
  {
    return $this->name;
  }

  public function setName(?string $name): static
  {
    $this->name = $name;

    return $this;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function setDescription(?string $description): static
  {
    $this->description = $description;

    return $this;
  }

  public function getDate(): ?\DateTimeImmutable
  {
    return $this->date;
  }

  public function setDate(\DateTimeImmutable $date): static
  {
    $this->date = $date;

    return $this;
  }

  public function getVersion(): int
  {
    return $this->version;
  }

  public function setVersion(int $version): static
  {
    $this->version = $version;

    return $this;
  }

  /**
   * @return Collection<int, OrderItem>
   */
  public function getItems(): Collection { return $this->items; }

  public function addItem(OrderItem $item): void
  {
    if (!$this->items->contains($item)) {
      $this->items->add($item);
      $item->setOrderRef($this);
    }
  }

}
