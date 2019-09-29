<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/9/29
 * Time: 10:17
 */
class OxygenQueue{
    private $id=0;//id号
    private $name='';//名字
    private $list=[];//任务队列
    private $fileUrl='';//日志内容存储目录
    private $autoKey=0;//自增键
    public function __construct($id,$name,$fileUrl){
        $this->id=$id;
        $this->name=$name;
        $this->fileUrl=$fileUrl;
    }
    /*自动下载缓存数据到变量中*/
    private function autoDownLoad(){
        if(file_exists($this->fileUrl.'/'.$this->id.'/'.$this->id.'.cache')) {
            $this->list = json_decode(file_get_contents($this->fileUrl . '/' . $this->id . '/' . $this->id . '.cache'), true);
        }
    }
    /*
     * 添加任务
     * command:执行的脚本命令
     * params:参数
     * type:类型
     * overTime:到期时间
     * */
    public function add($command,$params,$type,$overTime){
        $this->autoDownLoad();
        $this->getConf();
        $id=$this->autoKey+1;
        $row['command']=$command;
        $row['params']=$params;
        $row['type']=$type;
        $row['overTime']=$overTime;
        $this->list["{$id}"]=$row;
        $this->createQueueCache();
        $conf=$this->getConf();
        $this->autoKey=$conf['autoKey']+1;
        $this->createConf();
    }
    /*定时扫描*/
    public function scanning(){
        $oldConf=$this->getConf();
        //$this->list=json_decode($this->getQueueCache(),true);
        $this->autoDownLoad();
        while (true) {
            $newConf=$this->getConf();
            if($oldConf['autoKey']!=$newConf['autoKey']){
                $oldConf=$newConf;
                $this->autoDownLoad();
            }
            foreach ($this->list as $key=>$value){
                if(time()>$value['overTime']){
                    exec($value['command'],$out);
                    if($out[0]=='success') {
                        $this->removeCommand($key);
                    }
                    else{
                        $this->createErrorLog(json_encode($value).' 命令id为:'.$key,'未接收到返回值');
                    }
                }
            }
            sleep(1);
        }
    }
    /*移除指定命令*/
    public function removeCommand($id){
        //$this->list=json_decode($this->getQueueCache(),true);
        $this->autoDownLoad();
        unset($this->list["{$id}"]);
        $this->createQueueCache();
    }
    /*初始化目录*/
    public function init(){
        if(!is_dir($this->fileUrl.'/'.$this->id)){
            mkdir($this->fileUrl.'/'.$this->id);
        }
        if(!file_exists($this->fileUrl.'/'.$this->id.'/'.$this->id.'.conf')) {
            $this->createConf();
        }
        if(!file_exists($this->fileUrl.'/'.$this->id.'/'.$this->id.'.cache')) {
            $this->createQueueCache();
        }
    }
    /*生成配置文件*/
    private function createConf(){
        $conf['id']=$this->id;
        $conf['name']=$this->name;
        $conf['autoKey']=$this->autoKey;
        file_put_contents($this->fileUrl.'/'.$this->id.'/'.$conf['id'].'.conf',json_encode($conf));
        return true;
    }
    /*生成错误日志信息*/
    private function createErrorLog($errorMsg,$type){
        $str="[{$type}]类型错误:  ".$errorMsg;
        file_put_contents($this->fileUrl.'/'.$this->id.'/'.$this->id.'.error',$str);
    }
    /*获取配置文件信息*/
    public function getConf(){
        $conf=json_decode(file_get_contents($this->fileUrl.'/'.$this->id.'/'.$this->id.'.conf'),true);
        $this->autoKey=$conf['autoKey'];
        return $conf;
    }
    /*生成队列缓存*/
    private function createQueueCache(){
        file_put_contents($this->fileUrl.'/'.$this->id.'/'.$this->id.'.cache',json_encode($this->list));
    }
    /*获取队列缓存*/
    private function getQueueCache(){
        $cache=file_get_contents($this->fileUrl.'/'.$this->id.'/'.$this->id.'.cache');
        return $cache;
    }
}