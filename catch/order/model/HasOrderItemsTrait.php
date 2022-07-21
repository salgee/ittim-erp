<?php
namespace catchAdmin\order\model;

trait HasOrderItemsTrait
{
    /**
     *
     * @time 2021年02月19日
     * @return mixed
     */
    public function items()
    {
        return $this->belongsToMany(OrderItemRecords::class, 'order_has_items', 'order_record_id', 'id');
    }

  /**
   *
   * @time 2021年02月19日
   * @param array $fields
   * @return mixed
   */
    public function getItems()
    {
        return $this->items()->select();
    }

    /**
     *
     * @time 2021年02月19日
     * @param array $items
     * @return mixed
     */
    public function attachItems(array $items)
    {
        if (empty($items)) {
            return true;
        }

        sort($items);

        return $this->items()->attach($items);
    }

    /**
     *
     * @time 2021年02月19日
     * @param array $items
     * @return mixed
     */
    public function detachRoles(array $items = [])
    {
        if (empty($items)) {
            return $this->items()->detach();
        }

        return $this->items()->detach($items);
    }
}
