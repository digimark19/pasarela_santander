<!DOCTYPE html>
<?php
  require_once "SANTANDER.php";
  
  print_r(SANTANDER::makeButtonPayment());
  
  $linkPay=SANTANDER::makeButtonPayment('test','debug','no','es','no','web')["wtf"][0];
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<script> 
      function gtag_report_conversion(url,id) { 
        
        var callback = function () { if (typeof(url) != 'undefined') { window.location = url; } }; 
        gtag('event', 'conversion', 
        { 
          'send_to': 'AW-827127236/7_0MCKWLsYoDEMTrs4oD', 
          'value': 1.0, 'currency': 'MXN', 
          'transaction_id': id, 
          'event_callback': callback 
        }); 
        return false; 
      } 
    </script> 
<body>
    < 
</body>
</html>