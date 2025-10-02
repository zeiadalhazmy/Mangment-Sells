<?php
// lib/catalog.php

function db_categories(PDO $db): array {
    return $db->query("SELECT id,name,slug FROM categories ORDER BY name ASC")
              ->fetchAll(PDO::FETCH_ASSOC);
}

function db_products_count(PDO $db, ?int $categoryId=null, ?string $q=null): int {
    $where=[]; $args=[];
    if ($categoryId) { $where[]="category_id = :cat"; $args[':cat']=$categoryId; }
    if ($q)         { $where[]="(name LIKE :q OR description LIKE :q)"; $args[':q']="%{$q}%"; }
    $sql="SELECT COUNT(*) FROM products";
    if ($where) $sql.=" WHERE ".implode(' AND ',$where);
    $st=$db->prepare($sql); foreach($args as $k=>$v) $st->bindValue($k,$v);
    $st->execute(); return (int)$st->fetchColumn();
}

function db_products_page(PDO $db, ?int $categoryId, ?string $q, int $limit, int $offset): array {
    $where=[]; $args=[];
    if ($categoryId) { $where[]="category_id = :cat"; $args[':cat']=$categoryId; }
    if ($q)         { $where[]="(name LIKE :q OR description LIKE :q)"; $args[':q']="%{$q}%"; }
    $sql="SELECT id,name,price,image,category_id FROM products";
    if ($where) $sql.=" WHERE ".implode(' AND ',$where);
    $sql.=" ORDER BY id DESC LIMIT :lim OFFSET :off";
    $st=$db->prepare($sql);
    foreach($args as $k=>$v) $st->bindValue($k,$v);
    $st->bindValue(':lim',$limit,PDO::PARAM_INT);
    $st->bindValue(':off',$offset,PDO::PARAM_INT);
    $st->execute(); return $st->fetchAll(PDO::FETCH_ASSOC);
}