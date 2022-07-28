<?php

class SANTANDER{

  ////////////////////////////// PROCESS RESPONSE AFTER PAY///////////// $key = ($mode=="debug") ? "5dcc67393750523cd165f17e1efadd21" : "6CD3D18B4DD7680036A1A20B733A7D70"; //880C394F28F23BA2E1D846CA82C33645
  static function formatDateResponse($date){
    $date=str_replace('/','-',$date);
    $date=date("Y-m-d",strtotime($date));
    return $date;
  }

  static function processNotify($params="",$mode="prod"){
    if(empty($params)){ return "ERR"; }
    $key = "6CD3D18B4DD7680036A1A20B733A7D70";
    $respEncoded=self::desencriptar($params, $key);
    $xml = simplexml_load_string($respEncoded);
    $xml = json_encode($xml);
    return $xml;
  }

  static function setRequestPayment($params=""){
    if(empty($params)){ return "ERR"; }
    $params=json_decode($params);
    // print_r($params);    //get_object_vars($obj) ? TRUE : FALSE;
    $isUPDRES="";
    $idRes  = (is_object($params->reference)) ? "" : $params->reference ;
    $cName  = (is_object($params->cc_name)) ? "" : $params->cc_name ;
    $coderr = (is_object($params->cd_error)) ? "" : $params->cd_error ;
    $err = (is_object($params->nb_error)) ? "" : $params->nb_error ;
    $respDate=self::formatDateResponse($params->date);

    $status = (is_object($params->response)) ? "" : $params->response ;
    $pyInfo["status"]=$status;
    if($status=="approved"){
      //$resp=self::updateReservePayment();
      $pyInfo["isprepay"]="1";
      $pyInfo["methodpay"]="Prepago";
      $pyInfo["statuspay"]="1";
      $pyInfo["idres"]=$idRes;
      
      // $_SESSION["irRes"] = $idRes;
      $updPay=RESERVATIONS::updatePay($pyInfo);
      if($updPay=="1"){ $isUPDRES="ok"; }else{ $isUPDRES=$updPay; }
    }else{
      $pyInfo["isprepay"]="0";
      $pyInfo["methodpay"]="Declinado";
      $pyInfo["statuspay"]="0";
      $pyInfo["idres"]=$idRes;
      
      // $_SESSION["irRes"] = $idRes;
      $updPay=RESERVATIONS::updatePay($pyInfo);
    }
    $_SESSION["pyInfo"] = $pyInfo;
    //return true;
    //echo "WTG";
    $sql="Insert into
            bgt_pagos_santander
            (
              referencia,
              folio,
              response,
              codeResponse,
              codeError,
              nbError,
              dateOp,
              timeOp,
              ccName,
              email,
              ccNumber,
              ccType,
              ccMask,
              amount,
              updateReseve
            ) Values
            (
              '$idRes',
              '{$params->foliocpagos}',
              '$status',
              '{$params->cd_response}',
              '$coderr',
              '$err',
              '$respDate',
              '{$params->time}',
              '$cName',
              '{$params->email}',
              '{$params->cc_mask}',
              '{$params->cc_type}',
              '{$params->cc_mask}',
              '{$params->amount}',
              '$isUPDRES'
            )";
          //print_r($sql); return $sql;
    $resp=DB::simpleQuery($sql);
    return $resp;
  }

  static function updateReservePayment($params=""){

  }

  static function saveResponse($ppost="",$pget="",$xml=""){
    $fecha=date('Y-m-d H:m:s');
    $sql="insert into bgt_resp_santander (respPOST,respGET,respEncoded,creationDate) values ('$ppost','$pget','$xml','$fecha')";
    $resp=DB::simpleQuery($sql);
    return $resp;
  }

  ////////////////////////////// END PROCESS RESPONSE AFTER PAY//////////
  ////////////////////////////// MAKE BUTTON/////////////////////////////
 // Se crea el XLM y se encripta con la llave 
  static function makeString($params,$mode="prod"){
    $fechaVig=date('d/m/Y');
    if($mode=="debug"){
      $bussinesData='
      <business>
        <id_company>SNBX</id_company>
        <id_branch>01SNBXBRNCH</id_branch>
        <user>SNBXUSR01</user>
        <pwd>SECRETO</pwd>
      </business>';
    }else{
      $bussinesData='
      <business>
        <id_company>WJUA</id_company>
        <id_branch>0054</id_branch>
        <user>WJUASIUS1</user>
        <pwd>44T7YWP4IQ</pwd>
      </business>';
    }
    if(!empty($params["email"])){ $email=$params["email"]; }else{ $email="webmaster@grupoguepardo.com"; }
    $cadena=<<<cadena
    <P>
      $bussinesData
      <url>
        <reference>{$params["reference"]}</reference>
        <amount>{$params["monto"]}</amount>
        <moneda>MXN</moneda>
        <canal>W</canal>
        <omitir_notif_default>1</omitir_notif_default>
        <promociones>C</promociones>
        <st_correo>0</st_correo>
        <fh_vigencia>$fechaVig</fh_vigencia>
        <mail_cliente>$email</mail_cliente>
        <datos_adicionales>
          <data id="1" display="true">
            <label>Empresa</label>
            <value>Budget Mexico</value>
          </data>
          <data id="2" display="false">
            <label>Ciudad</label>
            <value></value>
          </data>
        </datos_adicionales>
      </url>
    </P>
cadena;
    //print_r($cadena);
    $encryptedString=self::cipherString($cadena,$mode);
    return $encryptedString;
  }
  //Se encripta el xml con la llavel
  static function cipherString($cadena,$mode="prod"){
    //SANDBOX  $key = '5dcc67393750523cd165f17e1efadd21'; //Llave de 128 bits
    //$key = '880C394F28F23BA2E1D846CA82C33645'; //Llave de 128 bits
    $key = ($mode=="debug") ? "5dcc67393750523cd165f17e1efadd21" : "6CD3D18B4DD7680036A1A20B733A7D70";
    $encryptedString=self::encriptar($cadena,$key);
    return $encryptedString;
  }

  static function requestGeneration($encryptedString,$mode="prod"){
    if($mode=="debug"){ //SANDBOX
      $cadena='<pgs><data0>SNDBX123</data0><data>'.$encryptedString.'</data></pgs>';
      $cadURL= "https://wppsandbox.mit.com.mx/gen";
      $cadHost="wppsandbox.mit.com.mx";
    }else{
      $cadena='<pgs><data0>9265655956</data0><data>'.$encryptedString.'</data></pgs>';
      $cadURL= "https://bc.mitec.com.mx/p/gen";
      $cadHost="bc.mitec.com.mx";

    }
    $encodedString = urlencode($cadena);
    $sendCad="xml=".$encodedString;
    $lengMsg=strlen($sendCad);
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $cadURL,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $sendCad,
      CURLOPT_HTTPHEADER => array(
        "Accept: */*",
        "Accept-Encoding: gzip, deflate",
        "Connection: keep-alive",
        "Content-Length: $lengMsg",
        "Content-Type: application/x-www-form-urlencoded",
        "Host: $cadHost",
        "cache-control: no-cache,no-cache"
      ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
      return "ERR";
      //echo "cURL Error #:" . $err;
    }
    return self::decipherResponseGeneration($response,$mode);
  }

  static function decipherResponseGeneration($originalString,$mode="prod"){
    // SANDOX $key = '5dcc67393750523cd165f17e1efadd21'; //Llave de 128 bits
    //$key = '880C394F28F23BA2E1D846CA82C33645'; //Llave de 128 bits
    $key = ($mode=="debug") ? "5dcc67393750523cd165f17e1efadd21" : "6CD3D18B4DD7680036A1A20B733A7D70";
    return self::desencriptar($originalString, $key);
  }

  static function constructButton($xml,$lang,$reint,$params=""){
    // $xml = simplexml_load_string($gatewayResponse);
    if($xml->cd_response="success"){
        if(!empty($xml->nb_response)){
          return "ERR";
        }
        $wtf=$xml->nb_url;
        $icon='<i class="lock icon"></i>';
        if($lang=="eng"){
          if($reint=="no"){
            $leyenda="Pay now";
          }else{
            $leyenda="Try again";
            $icon='<i class="redo icon"></i>';
          }
        }else{
          if($reint=="no"){
            $leyenda="Pagar ahora";
          }else{
            $leyenda="Reintentar pago";
            $icon='<i class="redo icon"></i>';
          }
        }
        $icon='';
        $bodyButton='<a class="ui right icon orange fluid button btncpay disabled" onclick="return gtag_report_conversion(\''.$wtf.'\',\''.$params["reference"].'\');" href="'.$wtf.'">'.$icon.'&ensp;'.$leyenda.'</a>';
        return $bodyButton;
    }else{
      return "ERR";
    }
  }

  static function makeButtonPayment($params="test",$mode="prod",$showbtn="yes",$lang="esp",$reint="no",$typePage='web'){
    // print_r($params);
    if(empty($params)){ return "ND"; }
    if($params=="test"){ $params=self::temporalParams(); }
    $encryptedString=self::makeString($params,$mode); // armar el xml
    $gatewayResponse=self::requestGeneration($encryptedString,$mode);//generar el link para el cobro


    $xml = simplexml_load_string($gatewayResponse); // convertir respuesta xml a obj
    if($typePage=="web"){
      if($xml->cd_response="success"){
        if(!empty($xml->nb_response)){
          return "ERR";
        }
        $params["wtf"]=$xml->nb_url;
      }
      // print_r($params);
      return $params;
    }

    if($showbtn!="no"){
      $butonBody = self::constructButton($xml,$lang,$reint,$params);
      return $butonBody;
    }
  }

  static function temporalParams(){
    $params["reference"]="BMX48";
    $params["monto"]=1.00;
    return $params;
  }

  //////////////////////////////END MAKE BUTTON////////////////////////
public static function encriptar($plaintext, $key128){

          $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-128-cbc'));

          $cipherText = openssl_encrypt ( $plaintext, 'AES-128-CBC', hex2bin($key128), 1, $iv);
          return base64_encode($iv.$cipherText);
        }
      /**
       * Permite descifrar una cadena a partir de un llave proporcionada
       * @param strToDecrypt
       * @param key
       * @return String con la cadena descifrada
       */
      public static function desencriptar($encodedInitialData, $key128){
        $encodedInitialData =  base64_decode($encodedInitialData);
        $iv = substr($encodedInitialData,0,16);
        $encodedInitialData = substr($encodedInitialData,16);
        $decrypted = openssl_decrypt($encodedInitialData, 'AES-128-CBC', hex2bin($key128), 1, $iv);
        return $decrypted;
      }
}

?>
