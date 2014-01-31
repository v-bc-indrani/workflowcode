
<?php

$json = '{"foo-bar": 12345}';

$obj = json_decode($json);
var_dump($obj);
print $obj->{'foo-bar'}; // 12345

?>
