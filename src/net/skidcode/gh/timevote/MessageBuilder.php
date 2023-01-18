<?php
namespace net\skidcode\gh\timevote;

class MessageBuilder
{
	private $prefix;
	public function __construct($s){
		$this->prefix = $s;
	}
	
	public function build($msg){
		return "[{$this->prefix}] $msg";
	}
}

