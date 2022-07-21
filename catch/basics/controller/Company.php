<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 15:43:13
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-09-07 15:26:24
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\request\CompanyRequest;
use catcher\exceptions\FailedException;
use catchAdmin\basics\model\CompanyQuota as CompanyQuotaModel;
use catchAdmin\basics\model\Company as companyModel;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Product;
use catcher\Code;
use catchAdmin\basics\model\Shop;
use catchAdmin\basics\model\CompanyWarehouse;

class Company extends CatchController
{
    protected $companyModel;
    protected $companyQuotaModel;
    protected $user;
    protected $companyWarehouse;

    public function __construct(
        CompanyModel $companyModel,
        CompanyQuotaModel $companyQuotaModel,
        Users $user,
        CompanyWarehouse $companyWarehouse
    ) {
        $this->companyModel = $companyModel;
        $this->companyQuotaModel = $companyQuotaModel;
        $this->user = $user;
        $this->companyWarehouse = $companyWarehouse;
    }

    /**
     * 列表
     * @time 2021年02月06日 15:43
     * @param Request $request 
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->companyModel->getList());
    }

    /**
     * 所有客户列表列表不进行权限限制
     * @time 2021年02月06日 15:43
     * @param Request $request 
     */
    public function allCompany(Request $request)
    {
        $list = $this->companyModel->field(['id', 'code', 'name', 'is_status', 'type', 'user_type', 'account_id'])->select();
        return CatchResponse::success($list);
    }
    /**
     * 保存信息
     * @time 2021年02月06日 15:43
     * @param Request $request 
     */
    public function save(CompanyRequest $request): \think\Response
    {
        $this->companyQuotaModel->startTrans();
        $company = $this->companyModel->storeBy($request->post());
        if (!empty($request->param('datajson'))) {
            $lists = json_decode($request->param('datajson'), true);
            $arr = [];
            $amountAll = array_sum(array_column($lists, 'quota'));
            foreach ($lists as $key => $id) {
                $arr[$key] = $id;
                $arr[$key]['company_id'] = $company;
            }
            if (!$this->companyQuotaModel->insertAllBy($arr)) {
                $this->companyQuotaModel->rollback();
            } else {
                // 修改额度
                $this->companyModel->where(['id' => $company])->increment('amount', $amountAll);
                $this->companyModel->where(['id' => $company])->increment('overage_amount', $amountAll);
                $this->companyQuotaModel->commit();
            }
            return CatchResponse::success($company);
        }
        $this->companyQuotaModel->commit();
        return CatchResponse::success($company);
    }
    /**
     * 更新
     * @time 2021年02月06日 10:42
     * @param Request $request 
     * @param $id
     */
    public function update(CompanyRequest $request, $id): \think\Response
    {
        $user = request()->user();
        $data = $request->post();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();
        unset($data['amount']);
        unset($data['overage_amount']);
        unset($data['is_status']);
        unset($data['created_at']);
        $company = $this->companyModel->updateBy($id, $data);

        return CatchResponse::success($company);
    }

    /**
     * 读取
     * @time 2021年02月06日 15:43
     * @param $id 
     */
    public function read($id): \think\Response
    {
        $data = $this->companyModel->findBy($id);
        if ($data) {
            $data['companyQuotaList'] = $this->companyQuotaModel->where('company_id', '=', $id)->select();
        }
        if (!empty($data->account_id)) {
            $data['userData'] = Users::where('id', $data->account_id)->withoutField('remember_token, password')->select();
        }
        return CatchResponse::success($data);
    }

    /**
     * 批量禁用
     * @time 2020/09/16
     * @param Request $request  
     */
    public function disable(Request $request): \think\Response
    {
        $data = $request->post();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 2
                ];
                $list[] = $row;
            }
            $this->companyModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
    /**
     * 批量启用 enable
     */
    public function enable(Request $request): \think\Response
    {
        $data = $request->post();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 1
                ];
                $list[] = $row;
            }
            $this->companyModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 删除
     * @time 2021年02月06日 15:43
     * @param $id
     */
    public function delete($id): \think\Response
    {
        // 客户有关联商品不可删除
        $productNum = Product::where('company_id', $id)->count();
        if (!empty($productNum)) {
            return CatchResponse::fail('有关联商品不可删除', Code::FAILED);
        }
        // 有关联店铺
        $shopNum = Shop::where('company_id', $id)->count();
        if (!empty($shopNum)) {
            return CatchResponse::fail('有关联店铺不可删除', Code::FAILED);
        }

        return CatchResponse::success($this->companyModel->deleteBy($id));
    }
    /**
     * 客户账号创建
     */
    public function accountCreated(Request $request, $id)
    {
        try {
            $dataObj = $request->post();
            if ($this->user->where('email', $dataObj['email'])->value('id')) {
                return CatchResponse::fail('账号邮箱已存在');
            }
            $this->companyModel->startTrans();
            $userId = $this->user->storeBy($dataObj);
            $roles = [4];
            $this->user->attachRoles($roles);
            $data['account_id'] =  $userId;
            // 更新账户信息
            if (!$this->companyModel->updateBy($id, $data)) {
                $this->companyModel->rollback();
                return CatchResponse::fail('添加失败');
            }
            $this->companyModel->commit();
            return CatchResponse::success('', '添加成功');
        } catch (\Exception $exception) {
            $this->companyModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 状态更新
     * @time 2020/09/16
     * @param Request $request  
     */
    public function verify(Request $request, $id): \think\Response
    {
        if (!in_array($request->param('is_status'), [0, 1])) {
            throw new FailedException('参数不正确');
        }
        return CatchResponse::success($this->companyModel->updateBy($id, $request->post()));
    }

    /**
     * 绑定仓库  bindWarehouse 
     */
    public function bindWarehouse(Request $request, $id)
    {
        try {
            $data = $request->param();
            $list = [];
            $this->companyWarehouse->startTrans();
            // 删除之前绑定
            $dataAll = $this->companyWarehouse->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->companyWarehouse->deleteBy($dataAll, $force = true);
            }
            if (isset($data['warehouse'])) {
                foreach ($data['warehouse'] as $valson) {
                    $row =  [
                        'company_id' => $id,
                        'warehouse_id' => $valson['warehouse_id'] ?? '',
                        'warehouse_fictitious_id' => $valson['warehouse_fictitious_id'] ?? '',
                        'creator_id' => $data['creator_id']
                    ];
                    $list[] = $row;
                }
            }
            if (!$this->companyWarehouse->insertAllBy($list)) {
                $this->companyWarehouse->rollback();
            }
            $this->companyWarehouse->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->companyWarehouse->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 查看绑定仓库列表
     */
    public function seeBindWarehouse(Request $request, $id)
    {
        $data = $this->companyWarehouse->where('company_id', $id)->select();
        return CatchResponse::success($data);
    }
}
