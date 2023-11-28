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

namespace Eccube\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Service\PurchaseFlow\InvalidItemException;
use Eccube\Service\PurchaseFlow\ItemCollection;

if (!class_exists('\Eccube\Entity\Cart')) {
    /**
     * Cart
     *
     * @ORM\Table(name="dtb_cart", indexes={
     *     @ORM\Index(name="dtb_cart_update_date_idx", columns={"update_date"})
     *  },
     *  uniqueConstraints={
     *     @ORM\UniqueConstraint(name="dtb_cart_pre_order_id_idx", columns={"pre_order_id"})
     *  }))
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\CartRepository")
     */
    class Cart extends AbstractEntity implements PurchaseInterface, ItemHolderInterface
    {
        use PointTrait;

        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string|null
         *
         * @ORM\Column(name="cart_key", type="string", nullable=true)
         */
        private $cart_key;

        /**
         * @var \Eccube\Entity\Customer|null
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        private $Customer;

        /**
         * @var bool
         */
        private $lock = false;

        /**
         * @var \Doctrine\Common\Collections\Collection<int,CartItem>
         *
         * @ORM\OneToMany(targetEntity="Eccube\Entity\CartItem", mappedBy="Cart", cascade={"persist"})
         * @ORM\OrderBy({"id" = "ASC"})
         */
        private $CartItems;

        /**
         * @var string|null
         *
         * @ORM\Column(name="pre_order_id", type="string", length=255, nullable=true)
         */
        private $pre_order_id = null;

        /**
         * @var string|float
         *
         * @ORM\Column(name="total_price", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
         */
        private $total_price;

        /**
         * @var float|int|string
         *
         * @ORM\Column(name="delivery_fee_total", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
         */
        private $delivery_fee_total;

        /**
         * @var int|null
         *
         * @ORM\Column(name="sort_no", type="smallint", nullable=true, options={"unsigned":true})
         */
        private $sort_no;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="update_date", type="datetimetz")
         */
        private $update_date;

        public function __construct()
        {
            $this->CartItems = new ArrayCollection();
        }

        /**
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return string|null
         */
        public function getCartKey()
        {
            return $this->cart_key;
        }

        /**
         * @param string $cartKey
         *
         * @return Cart
         */
        public function setCartKey(string $cartKey)
        {
            $this->cart_key = $cartKey;

            return $this;
        }

        /**
         * @return bool
         *
         * @deprecated 使用しないので削除予定
         */
        public function getLock()
        {
            return $this->lock;
        }

        /**
         * @param  bool                $lock
         *
         * @return \Eccube\Entity\Cart
         *
         * @deprecated 使用しないので削除予定
         */
        public function setLock($lock)
        {
            $this->lock = $lock;

            return $this;
        }

        /**
         * @return string|null
         */
        public function getPreOrderId()
        {
            return $this->pre_order_id;
        }

        /**
         * @param  string|integer|null $pre_order_id
         *
         * @return \Eccube\Entity\Cart
         */
        public function setPreOrderId($pre_order_id)
        {
            $this->pre_order_id = $pre_order_id;

            return $this;
        }

        /**
         * @param  CartItem            $CartItem
         *
         * @return \Eccube\Entity\Cart
         */
        public function addCartItem(CartItem $CartItem)
        {
            $this->CartItems[] = $CartItem;

            return $this;
        }

        /**
         * カートの中に出荷データがないので、空のコレクションを返します。
         *
         * @return ArrayCollection<int, empty>
         */
        public function getShippings()
        {
            return new ArrayCollection();
        }

        /**
         * @return \Eccube\Entity\Cart
         */
        public function clearCartItems()
        {
            $this->CartItems->clear();

            return $this;
        }

        /**
         * @return \Doctrine\Common\Collections\Collection<int,CartItem>
         */
        public function getCartItems()
        {
            return $this->CartItems;
        }

        /**
         * Alias of getCartItems()
         *
         * @return ItemCollection<int,\Eccube\Entity\ItemInterface>
         */
        public function getItems()
        {
            return (new ItemCollection($this->getCartItems()))->sort();
        }

        /**
         * @param  \Doctrine\Common\Collections\Collection<int,CartItem> $CartItems
         *
         * @return \Eccube\Entity\Cart
         */
        public function setCartItems($CartItems)
        {
            $this->CartItems = $CartItems;

            return $this;
        }

        /**
         * Set total.
         *
         * @param float|string|integer $total_price
         *
         * @return Cart
         */
        public function setTotalPrice($total_price)
        {
            $this->total_price = $total_price;

            return $this;
        }

        /**
         * @return float|int|string
         */
        public function getTotalPrice()
        {
            return $this->total_price;
        }

        /**
         * Alias of setTotalPrice.
         *
         * @param float|int|string $total
         *
         * @return Cart
         */
        public function setTotal($total)
        {
            return $this->setTotalPrice($total);
        }

        /**
         * Alias of getTotalPrice
         */
        public function getTotal()
        {
            return $this->getTotalPrice();
        }

        /**
         * @return integer
         */
        public function getTotalQuantity()
        {
            $totalQuantity = 0;
            foreach ($this->CartItems as $CartItem) {
                $totalQuantity += $CartItem->getQuantity();
            }

            return $totalQuantity;
        }

        /**
         * @param ItemInterface $item
         *
         * @return void
         */
        public function addItem(ItemInterface $item)
        {
            if($item instanceof CartItem) {
                $this->CartItems->add($item);
            }
        }

        /**
         * @param ItemInterface $item
         *
         * @return void
         */
        public function removeItem(ItemInterface $item)
        {
            if($item instanceof CartItem){
                $this->CartItems->removeElement($item);
            }
        }

        /**
         * 個数の合計を返します。
         *
         * @return integer
         */
        public function getQuantity()
        {
            return $this->getTotalQuantity();
        }

        /**
         * {@inheritdoc}
         *
         * @param float|int|string $total
         *
         * @return Cart
         */
        public function setDeliveryFeeTotal($total)
        {
            $this->delivery_fee_total = $total;

            return $this;
        }

        /**
         * {@inheritdoc}
         */
        public function getDeliveryFeeTotal()
        {
            return $this->delivery_fee_total;
        }

        /**
         * @return Customer|null
         */
        public function getCustomer(): ?Customer
        {
            return $this->Customer;
        }

        /**
         * @param Customer|null $Customer
         *
         * @return Cart
         */
        public function setCustomer(Customer $Customer = null)
        {
            $this->Customer = $Customer;

            return $this;
        }

        /**
         * Set sortNo.
         *
         * @param int|null $sortNo
         *
         * @return Cart
         */
        public function setSortNo($sortNo = null)
        {
            $this->sort_no = $sortNo;

            return $this;
        }

        /**
         * Get sortNo.
         *
         * @return int|null
         */
        public function getSortNo()
        {
            return $this->sort_no;
        }

        /**
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return Cart
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;

            return $this;
        }

        /**
         * Get createDate.
         *
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * Set updateDate.
         *
         * @param \DateTime $updateDate
         *
         * @return Cart
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;

            return $this;
        }

        /**
         * Get updateDate.
         *
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }

        /**
         * {@inheritdoc}
         *
         * @param int|float|string $total
         *
         * @return void
         */
        public function setDiscount($total)
        {
            // TODO quiet
        }

        /**
         * {@inheritdoc}
         *
         * @param int|float|string $total
         *
         * @return void
         */
        public function setCharge($total)
        {
            // TODO quiet
        }

        /**
         * {@inheritdoc}
         *
         * @param int|float|string $total
         *
         * @return void
         *
         * @deprecated
         */
        public function setTax($total)
        {
            // TODO quiet
        }

        /**
         * 注文ではないので、nullを返します。
         *
         * @return null
         */
        public function getOrderStatus()
        {
            return null;
        }

        /**
         * {@inheritdoc}
         *
         * @return ArrayCollection<int, empty>
         */
        public function getProductOrderItems()
        {
            return new ArrayCollection();
        }
    }
}
