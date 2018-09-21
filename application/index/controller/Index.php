<?php
namespace app\index\controller;
use think\Controller;
use Think\Db;
class City extends Controller
{
    // 第一执行，获取所有的省
    public function index()
    {
        header("content-type:text/html;charset=utf-8");
        include_once('./simple/simple_html_dom.php');
        $html = file_get_html('http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/index.html');
        $re = [];
        foreach($html->find('a') as $e){
            $r = parse_url($e->href);
            if(!isset($r['host'])){
                $re[] = ['links' => $e->href,'texts' => iconv('GB2312', 'UTF-8', str_replace("<br/>","",$e->innertext)), 'pid' => 0];
            }
        }
        if(Db::name('addses')->insertAll($re)){
            echo 'ok';
        }else {
            echo 'error';
        }
        return $this->fetch('');
    }

    // 第二执行，获取基于第二的所有市区
    public function getlinks()
    {
        if(request()->isAjax()){
            $param = request()->param();
            if(isset($param['init']) && ($param['init'] == 'init')){
                $data = Db::table('addses')->select();
                $count = count($data);
                return ['code' => 1,'msg' => '','count' => $count];
            }else if (isset($param['init']) && ($param['init'] == 'start')) {
                $result = [];
                $have = cache('alls');
                $current = '';
                if(!$have && ($have != 'end')){
                    $all = Db::table('addses')->field('id')->select();
                    $alls = [];
                    foreach ($all as $key => $value) {
                        $alls[] = $value['id'];
                    }
                    $current = current($alls);
                    array_shift($alls);
                    cache('alls',$alls);
                }else{
                    $current = current($have);
                    array_shift($have);
                    if(count($have) == '0'){
                        cache('alls','end');
                    }else{
                        cache('alls',$have);
                    }
                }
                // $current 为要执行的id
                // 执行变更数据，移动缩略图，并且处理相册，内容
                $re = $this->dealLinks($current);
                $result['surplus'] = count(cache('alls'));
                $result['init'] = 'start';
                $result['count'] = intval($param['count']);
                $result['data'] = $re['success'];
                $result['processed'] = $param['processed'] + 1;
                $result['code'] = 1;
                return $result;
            }
        }
        return $this->fetch('index');
    }

    public function dealLinks($current)
    {
        sleep(5);
        $value = Db::table('addses')->where('id',$current)->find();
        $url = "http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/";
        $allUrl = $url.$value['links'];
        header("content-type:text/html;charset=utf-8");
        include_once('./simple/simple_html_dom.php');
        $html = file_get_html($allUrl);
        $success = 0;
        foreach($html->find('a') as $e){
            $r = parse_url($e->href);
            $str = iconv('GB2312', 'UTF-8', str_replace("<br/>","",$e->innertext));
            if(!isset($r['host']) && !is_numeric($str)){
                $re = ['links' => $e->href,'texts' => $str, 'pid' => $value['links']];
                if(Db::name('addses')->insert($re)){
                    $success += 1;
                }
            }
        }
        return ['success' => $success];
    }

    // 第三执行,获取基于第二执行获取的第三级数据
    public function twolinks()
    {
        if(request()->isAjax()){
            $param = request()->param();
            $where[] = ['pid', 'neq', 0];
            if(isset($param['init']) && ($param['init'] == 'init')){
                $data = Db::table('addses')->where($where)->select();
                $count = count($data);
                return ['code' => 1,'msg' => '','count' => $count];
            }else if (isset($param['init']) && ($param['init'] == 'start')) {
                $result = [];
                $have = cache('alls_two');
                $current = '';
                if(!$have && ($have != 'end')){
                    $all = Db::table('addses')->field('id')->where($where)->select();
                    $alls = [];
                    foreach ($all as $key => $value) {
                        $alls[] = $value['id'];
                    }
                    $current = current($alls);
                    array_shift($alls);
                    cache('alls_two',$alls);
                }else{
                    $current = current($have);
                    array_shift($have);
                    if(count($have) == '0'){
                        cache('alls_two','end');
                    }else{
                        cache('alls_two',$have);
                    }
                }
                // $current 为要执行的id
                // 执行变更数据，移动缩略图，并且处理相册，内容
                $re = $this->dealTwoLinks($current);
                $result['surplus'] = count(cache('alls'));
                $result['init'] = 'start';
                $result['count'] = intval($param['count']);
                $result['data'] = $re['success'];
                $result['processed'] = $param['processed'] + 1;
                $result['code'] = 1;
                return $result;
            }
        }
        return $this->fetch('index');
    }

    public function dealTwoLinks($current)
    {
        sleep(15);
        $value = Db::table('addses')->where('id',$current)->find();
        $url = "http://www.stats.gov.cn/tjsj/tjbz/tjyqhdmhcxhfdm/2017/";
        $allUrl = $url.$value['links'];
        header("content-type:text/html;charset=utf-8");
        include_once('./simple/simple_html_dom.php');
        try {
            $html = file_get_html($allUrl);
            $success = 0;
            foreach($html->find('a') as $e){
                $r = parse_url($e->href);
                try {
                    $str = iconv('GB2312', 'UTF-8', str_replace("<br/>","",$e->innertext));
                } catch (\Exception $s) {
                    $str = iconv("GBK","UTF-8",str_replace("<br/>","",$e->innertext));
                }
                if(!isset($r['host']) && !is_numeric($str)){
                    $re = ['links' => $e->href,'texts' => $str, 'pid' => $value['links']];
                    if(Db::name('addses')->insert($re)){
                        $success += 1;
                    }
                }
            }
        } catch (\Exception $e) {
            sleep(30);
            $html = file_get_html($allUrl);
            $success = 0;
            foreach($html->find('a') as $e){
                $r = parse_url($e->href);
                try {
                    $str = iconv('GB2312', 'UTF-8', str_replace("<br/>","",$e->innertext));
                } catch (\Exception $s) {
                    $str = iconv("GBK","UTF-8",str_replace("<br/>","",$e->innertext));
                }
                if(!isset($r['host']) && !is_numeric($str)){
                    $re = ['links' => $e->href,'texts' => $str, 'pid' => $value['links']];
                    if(Db::name('addses')->insert($re)){
                        $success += 1;
                    }
                }
            }
        }
        return ['success' => $success];
    }

    // --------------------------------------------生成json树
    public function makejson()
    {
        $all = $value = Db::table('addses')->select();
        $tree = $this->maketree($all, 'links', 'pid','children');
        $newData = $this->dealTree($tree);
        $file_path = './static/web/plugs/';
	    if(!file_exists($file_path)){
	        mkdir($file_path,0777,true);
	        $result = file_put_contents($file_path."el-area.js",$this->decodeUnicode(json_encode($newData)));
	    }else{
	        $result = file_put_contents($file_path."el-area.js",$this->decodeUnicode(json_encode($newData)));
	    }
    }

    private function decodeUnicode($str)
    {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i',create_function('$matches','return mb_convert_encoding(pack("H*", $matches[1]), "UTF-8", "UCS-2BE");'),$str);
    }

    /**
     * 把返回的数据集转换成Tree
     * @param array $list 要转换的数据集
     * @param string $pid parent标记字段
     * @param string $level level标记字段
     * @return array
     */
    public function maketree($list, $pk='id', $pid = 'pid', $child = '_child', $root = 0)
    {
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId =  $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    private function dealTree($data, $newArray = [])
	{
		foreach ($data as $key => $value) {
			if(isset($value['children'])){
				$newArray[$key] = ['lable' => $value['texts'], 'children' => $this->dealTree($value['children'])];
			}else{
				$newArray[$key] = ['lable' => $value['texts']];
			}
		}
	    return $newArray;
	}
}
