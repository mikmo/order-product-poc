<?php
namespace App\Command;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\ElasticsearchOrderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
  name: 'app:elasticsearch:populate',
  description: 'Popola Elasticsearch con gli ordini esistenti, piccola demo per testare la connessione e l\'indicizzazione.',
)]
class ElasticsearchPopulateCommand extends Command
{
  private OrderRepository $orderRepository;
  private ElasticsearchOrderService $elasticsearchService;

  public function __construct(
    OrderRepository $orderRepository,
    ElasticsearchOrderService $elasticsearchService
  ) {
    parent::__construct();
    $this->orderRepository = $orderRepository;
    $this->elasticsearchService = $elasticsearchService;
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $io->title('Popolamento di Elasticsearch con ordini esistenti');

    $orders = $this->orderRepository->findAll();
    $total = count($orders);

    $io->progressStart($total);

    foreach ($orders as $order) {
      try {
        $this->elasticsearchService->createIndexOrder($order);
        $io->progressAdvance();
      } catch (\Exception $e) {
        $io->error(sprintf('Errore indicizzando ordine #%d: %s', $order->getId(), $e->getMessage()));
      }
    }

    $io->progressFinish();
    $io->success(sprintf('Indicizzati %d ordini su Elasticsearch', $total));

    return Command::SUCCESS;
  }
}
