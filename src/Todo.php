<?php

namespace App;

class Todo
{
	public array $todos = ['Item 1'];
	public string $draft='';
	public function add()
	{
		$this->todos[] = $this->draft;
		$this->draft = '';
	}
	public function render()
	{
		return Live::make()->render('views/todo.html', ['todos'=>collect($this->todos)]);
	}
}
