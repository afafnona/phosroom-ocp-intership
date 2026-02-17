<?php
require 'vendor/autoload.php';
$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml('<h1>Test PDF</h1>');
$dompdf->render();
$dompdf->stream("test.pdf");