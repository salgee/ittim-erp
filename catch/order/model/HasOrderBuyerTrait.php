<?php
namespace catchAdmin\order\model;

trait HasOrderBuyerTrait
{
    /**
     *
     * @time 2021年02月19日
     * @return mixed
     */
    public function buyer()
    {
        return $this->belongsTo(OrderBuyerRecords::class, 'order_has_buyer', 'order_record_id', 'id');
    }

  /**
   *
   * @time 2021年02月19日
   * @param array $fields
   * @return mixed
   */
    public function getBuyer()
    {
        return $this->buyer()->select();
    }

    /**
     *
     * @time 2021年02月19日
     * @param array $buyer
     * @return mixed
     */
    public function attachBuyer(array $buyer)
    {
        if (empty($buyer)) {
            return true;
        }

        sort($buyer);

        return $this->buyer()->attach($buyer);
    }

    /**
     *
     * @time 2021年02月19日
     * @param array $buyer
     * @return mixed
     */
    public function detachBuyer(array $buyer = [])
    {
        if (empty($buyer)) {
            return $this->buyer()->detach();
        }

        return $this->buyer()->detach($buyer);
    }
}
