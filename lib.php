<?php

function getHTML($url){
  $html = file($url);
  return $html;
}

function h($s) {
  return htmlspecialchars($s);
}

