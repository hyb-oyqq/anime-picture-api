<?php
$img_array = glob("img/*.{gif,jpg,png,webp}",GLOB_BRACE); /* 此处img更改为自己对应的图片文件夹*/
$img = array_rand($img_array); 
$dz = $img_array[$img];
header("Location:".$dz);
?>
