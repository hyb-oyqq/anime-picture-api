<?php
$img_array = glob("img-webp/*.webp" ,GLOB_BRACE);
$img = array_rand($img_array);
$dz = $img_array[$img];
header("Location:".$dz);
?>
