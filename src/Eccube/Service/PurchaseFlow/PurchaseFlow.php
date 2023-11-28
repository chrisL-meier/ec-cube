<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Service\PurchaseFlow;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;

class PurchaseFlow
{
    /**
     * @var string
     */
    protected $flowType;

    /**
     * @var ArrayCollection<int, ItemPreprocessor>|ItemPreprocessor[]
     */
    protected $itemPreprocessors;

    /**
     * @var ArrayCollection<int, ItemHolderPreprocessor>|ItemHolderPreprocessor[]
     */
    protected $itemHolderPreprocessors;

    /**
     * @var ArrayCollection<int, ItemValidator>|ItemValidator[]
     */
    protected $itemValidators;

    /**
     * @var ArrayCollection<int, ItemHolderValidator>|ItemHolderValidator[]
     */
    protected $itemHolderValidators;

    /**
     * @var ArrayCollection<int, ItemHolderPostValidator>|ItemHolderPostValidator[]
     */
    protected $itemHolderPostValidators;

    /**
     * @var ArrayCollection<int, DiscountProcessor>|DiscountProcessor[]
     */
    protected $discountProcessors;

    /**
     * @var ArrayCollection<int, PurchaseProcessor>|PurchaseProcessor[]
     */
    protected $purchaseProcessors;

    public function __construct()
    {
        $this->purchaseProcessors = new ArrayCollection();
        $this->itemValidators = new ArrayCollection();
        $this->itemHolderValidators = new ArrayCollection();
        $this->itemPreprocessors = new ArrayCollection();
        $this->itemHolderPreprocessors = new ArrayCollection();
        $this->itemHolderPostValidators = new ArrayCollection();
        $this->discountProcessors = new ArrayCollection();
    }

    /**
     * @param string $flowType
     * @return void
     */
    public function setFlowType($flowType)
    {
        $this->flowType = $flowType;
    }

    /**
     * @param ArrayCollection<int, PurchaseProcessor> $processors
     * @return void
     */
    public function setPurchaseProcessors(ArrayCollection $processors)
    {
        $this->purchaseProcessors = $processors;
    }

    /**
     * @param ArrayCollection<int, ItemValidator> $itemValidators
     * @return void
     */
    public function setItemValidators(ArrayCollection $itemValidators)
    {
        $this->itemValidators = $itemValidators;
    }

    /**
     * @param ArrayCollection<int, ItemHolderValidator> $itemHolderValidators
     * @return void
     */
    public function setItemHolderValidators(ArrayCollection $itemHolderValidators)
    {
        $this->itemHolderValidators = $itemHolderValidators;
    }

    /**
     * @param ArrayCollection<int, ItemPreprocessor> $itemPreprocessors
     * @return void
     */
    public function setItemPreprocessors(ArrayCollection $itemPreprocessors)
    {
        $this->itemPreprocessors = $itemPreprocessors;
    }

    /**
     * @param ArrayCollection<int, ItemHolderPreprocessor> $itemHolderPreprocessors
     * @return void
     */
    public function setItemHolderPreprocessors(ArrayCollection $itemHolderPreprocessors)
    {
        $this->itemHolderPreprocessors = $itemHolderPreprocessors;
    }

    /**
     * @param ArrayCollection<int, ItemHolderPostValidator> $itemHolderPostValidators
     * @return void
     */
    public function setItemHolderPostValidators(ArrayCollection $itemHolderPostValidators)
    {
        $this->itemHolderPostValidators = $itemHolderPostValidators;
    }

    /**
     * @param ArrayCollection<int, DiscountProcessor> $discountProcessors
     * @return void
     */
    public function setDiscountProcessors(ArrayCollection $discountProcessors)
    {
        $this->discountProcessors = $discountProcessors;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     * @return PurchaseFlowResult
     */
    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        $this->calculateAll($itemHolder);

        $flowResult = new PurchaseFlowResult($itemHolder);

        foreach ($itemHolder->getItems() as $item) {
            foreach ($this->itemValidators as $itemValidator) {
                $result = $itemValidator->execute($item, $context);
                $flowResult->addProcessResult($result);
            }
        }

        $this->calculateAll($itemHolder);

        foreach ($this->itemHolderValidators as $itemHolderValidator) {
            $result = $itemHolderValidator->execute($itemHolder, $context);
            $flowResult->addProcessResult($result);
        }

        $this->calculateAll($itemHolder);

        foreach ($itemHolder->getItems() as $item) {
            foreach ($this->itemPreprocessors as $itemPreprocessor) {
                $itemPreprocessor->process($item, $context);
            }
        }

        $this->calculateAll($itemHolder);

        foreach ($this->itemHolderPreprocessors as $holderPreprocessor) {
            $result = $holderPreprocessor->process($itemHolder, $context);
            if ($result) {
                $flowResult->addProcessResult($result);
            }

            $this->calculateAll($itemHolder);
        }

        foreach ($this->discountProcessors as $discountProcessor) {
            $discountProcessor->removeDiscountItem($itemHolder, $context);
        }

        $this->calculateAll($itemHolder);

        foreach ($this->discountProcessors as $discountProcessor) {
            $result = $discountProcessor->addDiscountItem($itemHolder, $context);
            if ($result) {
                $flowResult->addProcessResult($result);
            }
            $this->calculateAll($itemHolder);
        }

        foreach ($this->itemHolderPostValidators as $itemHolderPostValidator) {
            $result = $itemHolderPostValidator->execute($itemHolder, $context);
            $flowResult->addProcessResult($result);

            $this->calculateAll($itemHolder);
        }

        return $flowResult;
    }

    /**
     * 購入フロー仮確定処理.
     *
     * @param ItemHolderInterface $target
     * @param PurchaseContext $context
     * @return void
     * @throws PurchaseException
     */
    public function prepare(ItemHolderInterface $target, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        foreach ($this->purchaseProcessors as $processor) {
            $processor->prepare($target, $context);
        }
    }

    /**
     * 購入フロー確定処理.
     *
     * @param ItemHolderInterface $target
     * @param PurchaseContext $context
     * @return void
     *
     * @throws PurchaseException
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        foreach ($this->purchaseProcessors as $processor) {
            $processor->commit($target, $context);
        }
    }

    /**
     * 購入フロー仮確定取り消し処理.
     *
     * @param ItemHolderInterface $target
     * @param PurchaseContext $context
     * @return void
     */
    public function rollback(ItemHolderInterface $target, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        foreach ($this->purchaseProcessors as $processor) {
            $processor->rollback($target, $context);
        }
    }

    /**
     * @param PurchaseProcessor $processor
     * @return void
     */
    public function addPurchaseProcessor(PurchaseProcessor $processor)
    {
        $this->purchaseProcessors[] = $processor;
    }

    /**
     * @param ItemHolderPreprocessor $holderPreprocessor
     * @return void
     */
    public function addItemHolderPreprocessor(ItemHolderPreprocessor $holderPreprocessor)
    {
        $this->itemHolderPreprocessors[] = $holderPreprocessor;
    }

    /**
     * @param ItemPreprocessor $itemPreprocessor
     * @return void
     */
    public function addItemPreprocessor(ItemPreprocessor $itemPreprocessor)
    {
        $this->itemPreprocessors[] = $itemPreprocessor;
    }

    /**
     * @param ItemValidator $itemValidator
     * @return void
     */
    public function addItemValidator(ItemValidator $itemValidator)
    {
        $this->itemValidators[] = $itemValidator;
    }

    /**
     * @param ItemHolderValidator $itemHolderValidator
     * @return void
     */
    public function addItemHolderValidator(ItemHolderValidator $itemHolderValidator)
    {
        $this->itemHolderValidators[] = $itemHolderValidator;
    }

    /**
     * @param ItemHolderPostValidator $itemHolderValidator
     * @return void
     */
    public function addItemHolderPostValidator(ItemHolderPostValidator $itemHolderValidator)
    {
        $this->itemHolderPostValidators[] = $itemHolderValidator;
    }

    /**
     * @param DiscountProcessor $discountProcessor
     * @return void
     */
    public function addDiscountProcessor(DiscountProcessor $discountProcessor)
    {
        $this->discountProcessors[] = $discountProcessor;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @return void
     */
    protected function calculateTotal(ItemHolderInterface $itemHolder)
    {
        $total = array_reduce($itemHolder->getItems()->toArray(), function ($sum, ItemInterface $item) {
            $sum += $item->getPriceIncTax() * $item->getQuantity();

            return $sum;
        }, 0);
        $itemHolder->setTotal($total);
        // TODO
        if ($itemHolder instanceof Order) {
            // Order には PaymentTotal もセットする
            $itemHolder->setPaymentTotal($total);
        }
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @return void
     */
    protected function calculateSubTotal(ItemHolderInterface $itemHolder)
    {
        /** @var  \Eccube\Service\PurchaseFlow\ItemCollection $ProductClasses */
        $ProductClasses = $itemHolder->getItems()->getProductClasses();
        $total = $ProductClasses->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);
        // TODO
        if ($itemHolder instanceof Order) {
            // Order の場合は SubTotal をセットする
            $itemHolder->setSubTotal($total);
        }
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @return void
     */
    protected function calculateDeliveryFeeTotal(ItemHolderInterface $itemHolder)
    {
        /** @var \Eccube\Service\PurchaseFlow\ItemCollection $DeliveryFees */
        $DeliveryFees = $itemHolder->getItems()->getDeliveryFees();
        $total = $DeliveryFees->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);
        $itemHolder->setDeliveryFeeTotal($total);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @return void
     */
    protected function calculateDiscount(ItemHolderInterface $itemHolder)
    {
        /** @var  \Eccube\Service\PurchaseFlow\ItemCollection $Discounts */
        $Discounts = $itemHolder->getItems()->getDiscounts();
        $total = $Discounts->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);
        // TODO 後方互換のため discount には正の整数を代入する
        $itemHolder->setDiscount($total * -1);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @return void
     */
    protected function calculateCharge(ItemHolderInterface $itemHolder)
    {
        /** @var  \Eccube\Service\PurchaseFlow\ItemCollection $Charges */
        $Charges = $itemHolder->getItems()->getCharges();
        $total = $Charges->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);
        $itemHolder->setCharge($total);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @return void
     */
    protected function calculateTax(ItemHolderInterface $itemHolder)
    {
        $total = $itemHolder->getItems()
            ->reduce(function ($sum, ItemInterface $item) {
                if ($item instanceof OrderItem) {
                    $sum += $item->getTax() * $item->getQuantity();
                } else {
                    $sum += ($item->getPriceIncTax() - $item->getPrice()) * $item->getQuantity();
                }

                return $sum;
            }, 0);
        $itemHolder->setTax($total);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @return void
     */
    protected function calculateAll(ItemHolderInterface $itemHolder)
    {
        $this->calculateDeliveryFeeTotal($itemHolder);
        $this->calculateCharge($itemHolder);
        $this->calculateDiscount($itemHolder);
        $this->calculateSubTotal($itemHolder); // Order の場合のみ
        $this->calculateTax($itemHolder);
        $this->calculateTotal($itemHolder);
    }

    /**
     * PurchaseFlow をツリー表示します.
     *
     * @return string
     */
    public function dump()
    {
        $callback = function ($processor) {
            return get_class($processor);
        };
        $flows = [
            0 => $this->flowType.' flow',
            'ItemValidator' => $this->itemValidators->map($callback)->toArray(),
            'ItemHolderValidator' => $this->itemHolderValidators->map($callback)->toArray(),
            'ItemPreprocessor' => $this->itemPreprocessors->map($callback)->toArray(),
            'ItemHolderPreprocessor' => $this->itemHolderPreprocessors->map($callback)->toArray(),
            'DiscountProcessor' => $this->discountProcessors->map($callback)->toArray(),
            'ItemHolderPostValidator' => $this->itemHolderPostValidators->map($callback)->toArray(),
        ];
        $tree = new \RecursiveTreeIterator(new \RecursiveArrayIterator($flows));
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_RIGHT, ' ');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_LAST, '　');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, '│');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_HAS_NEXT, '├');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_LAST, '└');
        $out = '';
        foreach ($tree as $key => $value) {
            if (is_numeric($key)) {
                $out .= $value.PHP_EOL;
            } else {
                $out .= $key.PHP_EOL;
            }
        }

        return $out;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->dump();
    }
}
