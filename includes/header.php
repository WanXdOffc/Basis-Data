<?php
/**
 * Header include - outputs the full HTML <head> and opens <body>
 * Usage: include_once __DIR__ . '/../includes/header.php';
 *
 * Variables expected from calling page:
 *   $pageTitle  - string, shown in <title>
 *   $activePage - string, used to mark sidebar link active
 */

if (!isset($pageTitle))  $pageTitle  = 'Dashboard';
if (!isset($activePage)) $activePage = 'dashboard';

$siteTitle = 'Kuliner Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> &mdash; <?= $siteTitle ?></title>
  <meta name="description" content="Sistem Manajemen Restoran Kuliner - <?= htmlspecialchars($pageTitle) ?>">

  <!-- Google Fonts: Nunito -->
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,300;0,400;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Main Stylesheet -->
  <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
