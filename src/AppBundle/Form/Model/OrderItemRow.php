<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 05.06.2018
 * Time: 17:08
 */

namespace AppBundle\Form\Model;

class OrderItemRow
{
    /** @var  string $name */
    protected $code;
    /** @var  string $name */
    protected $name;
    /** @var  integer $name */
    protected $quantity;
    /** @var  float $name */
    protected $price;
    /** @var  float $name */
    protected $total;

    /**
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Integer
     */
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    /**
     * @param Integer $quantity
     */
    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    /**
     * @return float
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getTotal(): ?float
    {
        return $this->total;
    }

    /**
     * @param float $total
     */
    public function setTotal(float $total): void
    {
        $this->total = $total;
    }



}