<?php

namespace App;

class Counter
{
	public function render()
	{
		// var_dump($params);die;
		return Live::make()->render('views/index.html',['title'=>"echo titulo",'teste'=>'asdfasdfasdfasdf']);
	}
}
