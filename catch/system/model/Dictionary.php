<?php


namespace catchAdmin\system\model;

use app\Request;
use catchAdmin\system\model\DictionaryData;
use catchAdmin\system\model\search\DictionarySearch;
use catcher\exceptions\FailedException;
use catcher\base\CatchModel as Model;

class Dictionary extends Model
{
	use DictionarySearch;

    protected $name = 'dictionary';

    protected $field = [
        'id', // 
        'dict_type', //字典类型
		'dict_name', // 字典名称
		'dict_value', // 字典值(固定的不可改变)
		'creator_id', // 创建人ID
		'created_at', // 创建时间
		'updated_at', // 更新时间
		'deleted_at', // 软删除
    ];
    
	/**
     * 获取自定列表和字典的数据
     * @param $searchKey
     * @param $page
     * @return array
     * @throws \think\exception\DbException
     */
    public function getDictionariesWithData() {

		$result = $this->catchSearch()
			->where('deleted_at', 0)
            ->order($this->aliasField('created_at'), 'desc')
			->select()
			->toArray();
		if (count($result) == 0) {
            return $result;
        }
        $dictValues = [];
        foreach ($result as $datum) {
            $dictValues[] = $datum['dict_value'];
        }
		$dictDatas = $this->getAllDataInValues($dictValues);
        foreach ($result as &$data) {
            $data['data'] = isset($dictDatas[$data['dict_value']]) ? $dictDatas[$data['dict_value']] : [];
        }
        return $result;
    }
    /**
     * 删除数据字典值
     */
    public function delDictionary($request) {
        $id = $request->param('id');
        $value = $request->param('value');
        $variety = $request->param('variety');
        $type = $request->param('type');
        if (empty($id)) {
            throw new FailedException('请传入数据id'); 
        }
        $bool = DictionaryData::destroy($id);
        return $bool;
    }
	
	/**
     * 获取字典集合中的所有数据
     * @param $values
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllDataInValues($values) {
        $dataResult = DictionaryData::whereIn('dict_value' , $values)
            ->order('sort asc')
            ->select()
			->toArray();
        $data = [];
        if (!$dataResult) {
            return [];
        }
        foreach ($dataResult as $item) {
            $data[$item['dict_value']][] = $item;
		}
        return $data;
    }

    
    /**
     * 根据字典值ID查询指定字典值的内容列表
     * @param $ctype
     * @param $ckey
     */
    public function getDictionaryDataDetail($dictValue) {
        $dict = $this->catchSearch()
            ->field('id, dict_name, dict_value, created_at')
            ->where(['dict_value' => $dictValue, 'deleted_at' => 0])
            ->find();
        if (!$dict) {
            return [];
        }
        $dict['data'] = DictionaryData::field('dict_data_name, dict_data_value')
            ->where([
                'dict_value' => $dictValue
            ])
            ->order('sort')
            ->column('dict_data_value,dict_data_name');
        return $dict;
    }

    /**
     * 根据字典类型查询指定字典值的内容列表
     * @param $ctype
     * @param $ckey
     */
    public function getDictionaryDataDetailByType($dictType) {
        $dict = $this->catchSearch()
            ->field('dict_name, dict_value, id, dict_type')
            ->where(['dict_type' => $dictType, 'deleted_at' => 0])
            ->find();
        if (!$dict) {
            return [];
        }
        $dict['data'] = DictionaryData::field('dict_data_name, dict_data_value, id')
            ->where([
                'dict_value' => $dict['dict_value']
            ])
            ->order('sort')
            ->column('dict_data_value,dict_data_name, id');
        return $dict;
    }

    /**
     * 添加数据字典
     */
    public function addDictionary($request) {
        $dictName = $request->param('dict_name');
        $dictArray = json_decode($request->param('dict_data'), true);
        if (!is_array($dictArray)) {
            throw new FailedException('数据字典数据格式不正确');
        }
        if ($this->where('dict_name', $dictName)->find()) {
            throw new FailedException('字典名称 [' . $dictName . ']已存在');
        }
        $dictMaxValue = $this->where('deleted_at', 0)->max('dict_value');
        $dictValue = $dictMaxValue + 1;
        $insertData = [];
        foreach($dictArray as $dict) {
            $insertData[] = [
                'dict_value' => $dictValue,
                'dict_data_name' => $dict['name'],
                'dict_data_value' => $dict['value'],
                'sort' => $dict['sort'],
                'creator_id' => $request->user()->id
            ];
        }
        try {
            $this->startTrans();
            if ($this->storeBy([
                'dict_name'   => $dictName,
                'dict_value'    => $dictValue,
                'creator_id' => $request->user()->id
            ]) === false) {
                throw new FailedException('添加失败');
            }
            if(!app(DictionaryData::class)->insertAllBy($insertData)){
                throw new FailedException('添加失败');
            };
        } catch (\Exception $exception) {
            $this->rollback();
            throw new FailedException($exception->getMessage());
        }
        $this->commit();
        return true;
    }

    /**
     * 更新数据字典
     */
    public function updateDictionary($dictValue, $request) {
        $dictName = $request->param('dict_name');
        $dictArray = json_decode($request->param('dict_data'), true);
        if (!is_array($dictArray)) {
            throw new FailedException('数据字典数据格式不正确');
        }
        $dictInfo = $this->field('id')
            ->where(['dict_value' => $dictValue, 'deleted_at' => 0])
            ->find();
        if (empty($dictInfo)) {
            throw new FailedException('数据字典不存在');
        }
        $updateData = [];
        foreach($dictArray as $dict) {
            $updateData[] = [
                'dict_value' => $dictValue,
                'dict_data_name' => $dict['name'],
                'dict_data_value' => $dict['value'],
                'sort' => $dict['sort'],
                'creator_id' => $request->user()->id
            ];
        }
        try {
            $this->startTrans();
            if ($this->updateBy($dictInfo['id'], [
                'dict_name'   => $dictName,
                'dict_value'    => $dictValue,
                'dict_type' => $request->param('dict_type')
            ]) === false) {
                throw new FailedException('更新失败');
            }
            if(!DictionaryData::destroy(function($query) use($dictValue){
                $query->where('dict_value', $dictValue);
            },true)){
                throw new FailedException('更新失败');
            };
            if(!app(DictionaryData::class)->insertAllBy($updateData)){
                throw new FailedException('更新失败');
            };
        } catch (\Exception $exception) {
            $this->rollback();
            throw new FailedException($exception->getMessage());
        }
        $this->commit();
    }

    



}