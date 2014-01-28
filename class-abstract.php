<?php
 abstract class Animal1{
 	
	public $name;
	public $age;
	
	public  function greet()
	{
		return " Oh I am ".$this->name ."and iam ".$this->age ." years old";
	} 
      abstract  function Description();
 			
 }
class dog extends Animal1{
	
	public function Description()
	{
		return " i am a Dog" ."\n";
	}
	public function greet()
	{
		return parent::greet()." and a dog ";
	}
}  
$dog1 = new dog();
$dog1->name="Royal";

$dog1->age=7;
echo $dog1->Description();

echo $dog1->greet();

?>