<?php


namespace catchAdmin\settlement\model\search;

trait ThirdPartLogisticsFeeSearch {
    public function searchReferenceNumberAttr ($query, $value, $data) {
        return $query->whereLike('reference_number', $value);
    }


    public function searchTrackingNumberAttr ($query, $value, $data) {
        return $query->whereLike('tracking_number', $value);
    }

    public function searchOrderNoAttr ($query, $value, $data) {
        return $query->whereLike('order_no', $value);
    }

    public function searchSkuAttr ($query, $value, $data) {
        return $query->whereLike('sku', $value);
    }
}