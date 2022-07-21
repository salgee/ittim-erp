<?php


namespace catchAdmin\settlement\model\search;

trait DischargeCargoFeeSearch {
    public function searchCompanyNameAttr ($query, $value, $data) {
        return $query->leftJoin('company', 'company.id = discharge_cargo_fee.company_id')
                     ->whereLike('company.name',$value);
    }
    
}