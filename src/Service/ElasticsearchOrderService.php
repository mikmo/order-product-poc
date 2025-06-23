<?php
// src/Service/ElasticsearchOrderService.php
namespace App\Service;

use Elastica\Query\MatchQuery;
use FOS\ElasticaBundle\Finder\FinderInterface;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use App\Entity\Order;
use Elastica\Document;

class ElasticsearchOrderService
{
  private $orderPersister;
  private $orderFinder;

  public function __construct(
    ObjectPersisterInterface $orderPersister,
    FinderInterface $orderFinder
  ) {
    $this->orderPersister = $orderPersister;
    $this->orderFinder = $orderFinder;
  }

  public function createIndexOrder(Order $order): void
  {
    $this->orderPersister->insertOne($order);
  }

  public function updateIndexOrder(Order $order): void
  {
    $this->orderPersister->replaceOne($order);
  }

  public function deleteIndexOrder(int $orderId): void
  {
    $this->orderPersister->deleteById($orderId);
  }

  private function prepareOrderData(Order $order): array
  {
    return [
      'id' => $order->getId(),
      'name' => $order->getName(),
      'description' => $order->getDescription(),
      'date' => $order->getDate()->format('c')
    ];
  }

  /**
   * Cerca ordini in Elasticsearch per nome, descrizione o range di data
   *
   * @param string|null $name Nome o parte del nome dell'ordine
   * @param string|null $description Descrizione o parte della descrizione dell'ordine
   * @param \DateTimeInterface|null $dateFrom Data di inizio (inclusa)
   * @param \DateTimeInterface|null $dateTo Data di fine (inclusa)
   * @param int $page Numero di pagina (inizia da 1)
   * @param int $size Risultati per pagina
   * @return array Risultati con metadati di paginazione
   */
  /**
   * Cerca ordini in Elasticsearch per nome, descrizione o range di data
   *
   * @param string|null $name Nome o parte del nome dell'ordine
   * @param string|null $description Descrizione o parte della descrizione dell'ordine
   * @param \DateTimeInterface|null $dateFrom Data di inizio (inclusa)
   * @param \DateTimeInterface|null $dateTo Data di fine (inclusa)
   * @param int $page Numero di pagina (inizia da 1)
   * @param int $size Risultati per pagina
   * @return array Risultati con metadati di paginazione
   */
  public function searchOrders(
    ?string $name = null,
    ?string $description = null,
    ?\DateTimeInterface $dateFrom = null,
    ?\DateTimeInterface $dateTo = null,
    int $page = 1,
    int $size = 10
  ): array {
    // Costruzione della query
    $boolQuery = new \Elastica\Query\BoolQuery();

    // Filtro per nome
    if ($name !== null && $name !== '') {
      $nameQuery = new \Elastica\Query\Wildcard('name', "*$name*");
      $boolQuery->addShould($nameQuery);

    }

    // Filtro per descrizione
    if ($description !== null && $description !== '') {
      $descriptionQuery = new \Elastica\Query\Wildcard('description', "*$description*");
      $boolQuery->addShould($descriptionQuery);
    }

    // Se abbiamo sia nome che descrizione, vogliamo che almeno uno corrisponda
    if ($name !== null && $name !== '' && $description !== null && $description !== '') {
      $boolQuery->setMinimumShouldMatch(1);
    }

    // Filtro per range di date
    if ($dateFrom !== null || $dateTo !== null) {
      $dateRange = new \Elastica\Query\Range();
      $rangeParams = [];

      if ($dateFrom !== null) {
        $rangeParams['gte'] = $dateFrom->format('c');
      }

      if ($dateTo !== null) {
        $rangeParams['lte'] = $dateTo->format('c');
      }

      $dateRange->addField('date', $rangeParams);
      $boolQuery->addMust($dateRange);
    }

    // Crea la query completa
    $query = new \Elastica\Query();

    // Se ci sono criteri di ricerca, usa bool query, altrimenti match_all
    if ($boolQuery->count() > 0) {
      $query->setQuery($boolQuery);
    } else {
      $query->setQuery(new \Elastica\Query\MatchAll());
    }

    // Calcola offset per paginazione
    $offset = ($page - 1) * $size;
    $query->setFrom($offset);
    $query->setSize($size);

    $query->addSort(['date' => ['order' => 'desc']]);

    // Ottieni l'adapter per recuperare il numero totale di risultati
    $paginatorAdapter = $this->orderFinder->createPaginatorAdapter($query);
    $total = $paginatorAdapter->getTotalHits();
    $maxPages = ceil($total / $size);

    $results = $this->orderFinder->find($query);

    // Prepara i risultati
    $orders = [];
    foreach ($results as $order) {
      // Ottieni l'entità usando il metodo findHybridResult, attualmente non necessario vista la configurazione orm persistence
//      $order = $hybridResult->getTransformed();

      $orders[] = [
        'id' => $order->getId(),
        'name' => $order->getName(),
        'description' => $order->getDescription(),
        'date' => $order->getDate()->format('c')
      ];
    }

    // Restituisci risultati con metadati di paginazione
    return [
      'total' => $total,
      'page' => $page,
      'size' => $size,
      'maxPages' => $maxPages,
      'orders' => $orders
    ];
  }


}
