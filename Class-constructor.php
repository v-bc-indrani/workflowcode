<?php
class Animal{
	 
	 public $name="No name animal";
	 
	 public function __construct($name)
	 {
	 	$this->name=$name;
	 } 
	 public function greet()
	 {
	 	return " i am an animal without any name ".var_dump($this->name) ."\n";
	 	
	 }
}
$dog = new Animal("Bob the dog");
echo $dog->name ."\n";

 class Dog extends Animal
 {
 	public function greet()
	{
		return " i am a dog and my name is".$this->name;
	}
 }
 $dog1= new Dog();
 $dog1->name="Royal";
 echo $dog1->greet();
 
?>