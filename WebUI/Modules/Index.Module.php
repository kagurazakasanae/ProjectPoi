<?php
if(!defined('IN_FRAMEWORK')){ die; }

ImportModule('Init');

class Index extends Init{
	
	public function ShowIndex(){
		$this -> XT -> setdata('thistitle', '首页');
		$this -> XT -> parse('index');
		H('normal');
		$this -> XT -> out();
		return true;
	}
}