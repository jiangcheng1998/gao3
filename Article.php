<?php

namespace app\Article\controller;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use think\Controller;
use think\Request;

class Article extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        // 接收参数
        $keyword = input("keyword");
        // 设置查询条件
        $where = [];
        if ($keyword){
            $where['title'] = ["like","%$keyword%"];
        }else{
            $keyword = "";
        }
        // 查询所有数据
        $list = \app\Article\model\Article::where($where)
            ->order("create_time desc")
            ->paginate(3,false,['query'=>['keyword'=>$keyword]]);
        return view("list",['data'=>$list]);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //返回到添加页面
        return view();
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //接收参数
        $param = $request->param();
        $result = $this->validate($param,
            [
                'title'  => 'require|max:50',
                'desc'   => 'require',
                'text'   => 'require',
            ]);
        if(true !== $result){
            // 验证失败 输出错误信息
            $this->error($result);
        }
        // 获取表单上传文件
        $file = request()->file('logo');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                //文件的路径
                $path = $info->getSaveName();
                $uploadMgr = new UploadManager();
                // 设置密钥
                $accessKey = "ePGEJRY6llAmZJL_46jBGd0fzAnDiaRla45L7o2P";
                $secretKey = "Q0Lze3KRbiSCYTdgs08r1lpBynHKWVMyPTI6Q00S";
                $auth = new Auth($accessKey, $secretKey);
                $token = $auth->uploadToken("jiangranya");
                // 设置时间
                $key = date("Y-m-d",time());
                // 上传
                list($ret, $error) = $uploadMgr->putFile($token, $key, './uploads/'.$path);
            }else{
                // 上传失败获取错误信息
                $this->error($file->getError());
            }
        }
        // 获取文件的路径
        $param['logo'] = $path;
        // 添加入库
        $res = \app\Article\model\Article::create($param,true);
        if ($res){
            $this->success("添加成功","Article/Article/index");
        }else{
            $this->error("添加失败");
        }
    }
    
    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete()
    {
        // 接收参数
        $ids = trim(input("id"),",");
        // 字符串转数组
        $ids = explode(",",$ids);
        // 循环处理数据
        foreach ($ids as $v){
            // 验证id
            if (!is_numeric($v)){
                return json(['code'=>500,'msg'=>"参数格式错误",'data'=>[]]);
            }
            // 拼写条件
            $where['id'] = $v;
            // 未显示的状态
            $where['display'] = 0;
            // 根据id删除数据
            \app\Article\model\Article::where($where)->delete();
        }
        return json(['code'=>200,'msg'=>"删除成功",'data'=>[]]);
    }
}
