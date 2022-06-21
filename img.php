<?php
$arr=file('img.txt');      /* 此处img.txt中img更改为自己对应的txt文件名*/
$n=count($arr)-1;
for ($i=1;$i<=1;$i++){
$x=rand(0,$n);
header("Location:".$arr[$x],"\n");
}
?>
