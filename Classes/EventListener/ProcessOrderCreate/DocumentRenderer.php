<?php
declare(strict_types=1);
namespace Extcode\CartPdf\EventListener\ProcessOrderCreate;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Domain\Repository\Order\ItemRepository as OrderItemRepository;
use Extcode\Cart\Event\Order\NumberGeneratorEvent;
use Extcode\Cart\Utility\OrderUtility;
use Extcode\CartPdf\Service\PdfService;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class DocumentRenderer
{
    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var OrderUtility
     */
    protected $orderUtility;

    /**
     * @var PdfService
     */
    protected $pdfService;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var array
     */
    protected $options = [];

    public function __construct(
        PersistenceManager $persistenceManager,
        OrderItemRepository $orderItemRepository,
        OrderUtility $orderUtility,
        PdfService $pdfService,
        array $options = []
    ) {
        $this->persistenceManager = $persistenceManager;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderUtility = $orderUtility;
        $this->pdfService = $pdfService;
        $this->options = $options;
    }

    public function __invoke(NumberGeneratorEvent $event): void
    {
        $orderItem = $event->getOrderItem();
        $this->settings = $event->getSettings();

        $generateDocuments = $this->settings['autoGenerateDocuments'];
        if (isset($this->options['autoGenerateDocuments'])) {
            $generateDocuments = $this->options['autoGenerateDocuments'];
        }

        if (empty($generateDocuments)) {
            return;
        }

        foreach ($generateDocuments as $documentType => $documentData) {
            if ((bool)$documentData) {
                $getterForNumber = 'get' . ucfirst($documentType) . 'Number';
                $setterForNumber = 'set' . ucfirst($documentType) . 'Number';
                $setterForDate = 'set' . ucfirst($documentType) . 'Date';

                if (!$orderItem->$getterForNumber()) {
                    $orderItem->$setterForNumber($orderItem->getOrderNumber());
                    $orderItem->$setterForDate(new \DateTime());
                }

                $this->pdfService->createPdf($orderItem, $documentType);
            }
        }

        $this->orderItemRepository->update($orderItem);
        $this->persistenceManager->persistAll();
    }
}
