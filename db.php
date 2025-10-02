<?php
$pdo = new PDO('sqlite:' . __DIR__ . '/store.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
