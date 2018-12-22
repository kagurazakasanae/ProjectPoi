<?php
//XT模板引擎 for Shokaku PHP Framework V2

class XT{
	private $t_type = ".html";	//模板文件类型
	private $subtp = array();	//字模板
	private $folder  = "./View/Tpl";	//模板目录
	private $t_cache = "./View/Compile";	//模板缓存目录
	private $compile = true;	//调试
	private $content_path = "";
	private $data = array();
	function XT(){
		if(!file_exists($this->t_cache)){
			if(!mkdir($this->t_cache)){
				throw new Exception("Failed to create cache directory:".$this->t_cache);
			}
		}
	}
	function setdata($k,$v){
		$this->data[$k]=$v;
	}
	function subtemplate($filename){
		if(is_array($filename)){
			foreach($filename as $v){
				$this->parse($v);
			}
		}else{
			$this->parse($filename);
		}
 	}
	function __parsedata($content){
		//模板包含
		$this->subtp=array();
		preg_match_all("/<!--\s+include\((.+)\)\s+-->/iu",$content,$rt);
			if(!empty($rt[1][0])){
				foreach($rt[1] as $v){
					if(!empty($v)){
						$this->subtp[$v]=$v;
					}
				}
				if(!empty($this->subtp)){
					$this->subtemplate($this->subtp,true);
				}
		}
		$content = preg_replace("/<!--\s+include\((.+)\)\s+-->/iu", "<?php include('$this->t_cache/\\1.php');?>", $content);
		//变量
		$content = preg_replace("/\{(\\\$[a-zA-Z0-9\[\]'_]+)\}/s", "<?=\\1?>", $content);
		//逻辑
		$content = preg_replace("/<!--\s+if\((.+)\):\s+-->/iu", "<?php if(\\1) { ?>", $content);
		$content = preg_replace("/<!--\s+else\s+-->/iu", "<?php }else{ ?>", $content);	//<--新增
		$content = preg_replace("/<!--\s+endif\s+-->/iu", "<?php } ?>", $content);
		//循环
		$content = preg_replace("/<!--\s+foreach\((.+)\):\s+-->/iu", "<?php foreach(\\1) { ?>", $content);
		$content = preg_replace("/<!--\s+endforeach\s+-->/iu", "<?php } ?>", $content);
		$content = preg_replace("/<!--\s+for\((.+)\):\s+-->/iu", "<?php for(\\1) { ?>", $content);	//<--新增
		$content = preg_replace("/<!--\s+endfor\s+-->/iu", "<?php } ?>", $content);	//<--新增
		//PHP CODE
		$content = preg_replace("/<!--\s+php\{(.+)\}\s+-->/iu", "<?php \\1 ?>", $content);	//<--新增
 		return $content;
 	}	
	function parse($filename=null,$sub=false){
		$filename=(empty($filename)?basename($_SERVER["PHP_SELF"],".php"):$filename);
		$content_path=$this->t_cache."/".$filename.".php";
		if(!file_exists($content_path)||$this->compile){
			$content=implode("",file($this->folder.'/'.$filename.$this->t_type));
			$content=$this->__parsedata($content);
			file_put_contents($content_path,$content);
		}
		!$sub?$this->content_path=$content_path:'';
	}
	function out(){
		extract($this->data);
		include_once($this->content_path);
	}
}
 ?>