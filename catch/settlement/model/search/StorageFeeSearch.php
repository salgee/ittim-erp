<?php


namespace catchAdmin\settlement\model\search;

use catchAdmin\permissions\model\Department;

trait StorageFeeSearch
{
    public function searchCompanyNameAttr($query, $value, $data)
    {
        return $query->whereLike('company_name', $value);
    }
    //部门
    public function searchDepartmentNameAttr($query, $value, $data)
    {
        $id = Department::where('department_name', $value)->value('id') ?? '';
        return $query->where('department_id', $id);
    }
}
