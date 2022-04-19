<?php
namespace  App\Repositories;

use App\Models\Admin\Factory;
use App\Repositories\Currency;

class Money
{
    /**
     * @var int
     */
    private $selling_price;
    /**
     * @var curency
     */
    private $curency;

    public function __construct($selling_price = 0)
    {
        $curency = Factory::select('curency')->first()->curency;
        $this->selling_price = $selling_price;
        $this->curency = new Currency($curency);
    }
    /**
     * @return Mixed
     */
    public function getAmount()
    {
        return $this->selling_price;
    }

    /**
     * @return curency
     */
    public function getcurency()
    {
        return $this->curency;
    }

    public function getBigDecimalAmount()
    {
        return $this->getAmount() / 100;
    }
}