<?php

namespace App\Controller\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateOrderRequest
{
    /**
     * @var string|null
     */
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Il nome deve contenere almeno {{ limit }} caratteri",
        maxMessage: "Il nome non può superare {{ limit }} caratteri"
    )]
    private $name;

    /**
     * @var string|null
     */
    #[Assert\Length(
        max: 500,
        maxMessage: "La descrizione non può superare {{ limit }} caratteri"
    )]
    private $description;

    /**
     * @var array|null
     */
    #[Assert\Type(type: "array", message: "Gli articoli devono essere in formato array")]
    #[Assert\Count(
        min: 1,
        minMessage: "È necessario specificare almeno un prodotto"
    )]
    #[Assert\All([
        new Assert\Collection([
            'fields' => [
                'productId' => [
                    new Assert\NotBlank(message: "L'ID del prodotto è obbligatorio"),
                    new Assert\Type(type: "integer", message: "L'ID del prodotto deve essere un numero intero"),
                    new Assert\Positive(message: "L'ID del prodotto deve essere un numero positivo")
                ],
                'quantity' => [
                    new Assert\NotBlank(message: "La quantità è obbligatoria"),
                    new Assert\Type(type: "integer", message: "La quantità deve essere un numero intero"),
                    new Assert\Positive(message: "La quantità deve essere un numero positivo")
                ]
            ],
            'allowExtraFields' => false,
            'allowMissingFields' => false
        ])
    ])]
    private $items;

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return self
     */
    public function setName($name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription($description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array|null $items
     * @return self
     */
    public function setItems($items): self
    {
        $this->items = $items;
        return $this;
    }
}
