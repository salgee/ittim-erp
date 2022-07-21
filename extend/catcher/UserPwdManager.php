<?php
namespace catcher;
use think\Config;

/**
 * 登录密码次数记次
 * Class UserPwdManager
 * @package 
 */
class UserPwdManager{

	//密码加锁
	public static function UserPwd($role, $account){
		$errMsg = '账号密码连续输入错误,您的账号已被锁定,自动解锁日期为';
		$userNumber = config('catch.login_number_config')[$role];
		$cacheKey = 'login_number_config' . '_' . $role . $account;
		if($userNumber['switch'] == true){
			if(cache($cacheKey)){
				//判断有没有大于设置次数
				$num = cache($cacheKey)['num'];
				if($num >= $userNumber['number']){
					return [
						'state' => 1,
						'mess' => $errMsg.date('Y-m-d H:i:s',cache($cacheKey)['time']+$userNumber['time'])
					];
				}else{
					cache($cacheKey,['time'=>time(),'num'=>$num+1],$userNumber['time']);
					$num = $userNumber['number']-cache($cacheKey)['num'];
					if($num == 0){
						return [
							'state' => 1,
							'mess' => $errMsg.date('Y-m-d H:i:s',cache($cacheKey)['time']+$userNumber['time'])
						];
					}
					return ['state'=>1, 'mess'=>'账号密码错误,您还可以输入'.$num.'次'];
				}
			}else{
				//第一次
				cache($cacheKey,['time'=>time(),'num'=>1],$userNumber['time']);
				$num = $userNumber['number']-1;
				return ['state'=>1, 'mess'=>'账号密码错误,您还可以输入'.$num.'次'];
			}
		}
		return ['state' => 0];
	}

	//密码解锁
	public static function UnlockUserPwd($role, $account){
		cache('login_number_config' . '_' . $role . $account, null);
	}

	//检测账号是否已经被锁定
	public static function isUnlock($role, $account){
		$userNumber = config('catch.login_number_config')[$role];
		$cacheKey = 'login_number_config' . '_' . $role . $account;
		if($userNumber['switch'] == true){
			if(cache($cacheKey)) {
				//判断有没有大于设置次数
				$num = cache($cacheKey)['num'];
				if ($num >= $userNumber['number']) {
					return [
						'state' => 1, 
						'mess' => '您的账号已被锁定,解锁日期为' . date('Y-m-d H:i:s', cache($cacheKey)['time'] + $userNumber['time'])
					];
				}
			}
		}
		return ['state' => 0];
	}
}