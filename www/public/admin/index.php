<?php
/**
 * Peradresavimas į vienintelį pagrindinį administravimo pulto adresą: /api/admin/
 */
$qs = !empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : '';
header('Location: /api/admin/' . $qs, true, 301);
exit;
