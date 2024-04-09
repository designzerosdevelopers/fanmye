<?php

namespace App\Services;

class Gwapi
{

    const APPROVED = 1;
    const DECLINED = 2;
    const ERROR = 3;

    // Properties for login, order, billing, shipping, and responses
    private $login = [];
    private $billing = [];
    private $responses = [];
    
  function setLogin($security_key) {
    $this->login['security_key'] = $security_key;
  }


  function setBilling($firstname,
        $lastname,
        $company,
        $address1,
        $address2,
        $city,
        $state,
        $zip,
        $country,
        $phone,
        $fax,
        $email,
        $website) {
    $this->billing['firstname'] = $firstname;
    $this->billing['lastname']  = $lastname;
    $this->billing['company']   = $company;
    $this->billing['address1']  = $address1;
    $this->billing['address2']  = $address2;
    $this->billing['city']      = $city;
    $this->billing['state']     = $state;
    $this->billing['zip']       = $zip;
    $this->billing['country']   = $country;
    $this->billing['phone']     = $phone;
    $this->billing['fax']       = $fax;
    $this->billing['email']     = $email;
    $this->billing['website']   = $website;
  }


  // Transaction Functions

  function doSale($amount, $ccnumber, $ccexp, $cvv="") {

    $query  = "";
    // Login Information
    $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
    // Sales Information
    $query .= "ccnumber=" . urlencode($ccnumber) . "&";
    $query .= "ccexp=" . urlencode($ccexp) . "&";
    $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
    $query .= "cvv=" . urlencode($cvv) . "&";
    // Billing Information
    $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
    $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
    $query .= "company=" . urlencode($this->billing['company']) . "&";
    $query .= "address1=" . urlencode($this->billing['address1']) . "&";
    $query .= "address2=" . urlencode($this->billing['address2']) . "&";
    $query .= "city=" . urlencode($this->billing['city']) . "&";
    $query .= "state=" . urlencode($this->billing['state']) . "&";
    $query .= "zip=" . urlencode($this->billing['zip']) . "&";
    $query .= "country=" . urlencode($this->billing['country']) . "&";
    $query .= "phone=" . urlencode($this->billing['phone']) . "&";
    $query .= "fax=" . urlencode($this->billing['fax']) . "&";
    $query .= "email=" . urlencode($this->billing['email']) . "&";
    $query .= "website=" . urlencode($this->billing['website']) . "&";
    return $this->_doPost($query);
  }

  function doAuth($amount, $ccnumber, $ccexp, $cvv="") {

    $query  = "";
    // Login Information
    $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
    // Sales Information
    $query .= "ccnumber=" . urlencode($ccnumber) . "&";
    $query .= "ccexp=" . urlencode($ccexp) . "&";
    $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
    $query .= "cvv=" . urlencode($cvv) . "&";
    // Billing Information
    $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
    $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
    $query .= "company=" . urlencode($this->billing['company']) . "&";
    $query .= "address1=" . urlencode($this->billing['address1']) . "&";
    $query .= "address2=" . urlencode($this->billing['address2']) . "&";
    $query .= "city=" . urlencode($this->billing['city']) . "&";
    $query .= "state=" . urlencode($this->billing['state']) . "&";
    $query .= "zip=" . urlencode($this->billing['zip']) . "&";
    $query .= "country=" . urlencode($this->billing['country']) . "&";
    $query .= "phone=" . urlencode($this->billing['phone']) . "&";
    $query .= "fax=" . urlencode($this->billing['fax']) . "&";
    $query .= "email=" . urlencode($this->billing['email']) . "&";
    $query .= "website=" . urlencode($this->billing['website']) . "&";
    return $this->_doPost($query);
  }

  function doCredit($amount, $ccnumber, $ccexp) {

    $query  = "";
    // Login Information
    $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
    // Sales Information
    $query .= "ccnumber=" . urlencode($ccnumber) . "&";
    $query .= "ccexp=" . urlencode($ccexp) . "&";
    $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
    // Billing Information
    $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
    $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
    $query .= "company=" . urlencode($this->billing['company']) . "&";
    $query .= "address1=" . urlencode($this->billing['address1']) . "&";
    $query .= "address2=" . urlencode($this->billing['address2']) . "&";
    $query .= "city=" . urlencode($this->billing['city']) . "&";
    $query .= "state=" . urlencode($this->billing['state']) . "&";
    $query .= "zip=" . urlencode($this->billing['zip']) . "&";
    $query .= "country=" . urlencode($this->billing['country']) . "&";
    $query .= "phone=" . urlencode($this->billing['phone']) . "&";
    $query .= "fax=" . urlencode($this->billing['fax']) . "&";
    $query .= "email=" . urlencode($this->billing['email']) . "&";
    $query .= "website=" . urlencode($this->billing['website']) . "&";
    $query .= "type=credit";
    return $this->_doPost($query);
  }

  function doOffline($authorizationcode, $amount, $ccnumber, $ccexp) {

    $query  = "";
    // Login Information
    $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
    // Sales Information
    $query .= "ccnumber=" . urlencode($ccnumber) . "&";
    $query .= "ccexp=" . urlencode($ccexp) . "&";
    $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
    $query .= "authorizationcode=" . urlencode($authorizationcode) . "&";
    // Billing Information
    $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
    $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
    $query .= "company=" . urlencode($this->billing['company']) . "&";
    $query .= "address1=" . urlencode($this->billing['address1']) . "&";
    $query .= "address2=" . urlencode($this->billing['address2']) . "&";
    $query .= "city=" . urlencode($this->billing['city']) . "&";
    $query .= "state=" . urlencode($this->billing['state']) . "&";
    $query .= "zip=" . urlencode($this->billing['zip']) . "&";
    $query .= "country=" . urlencode($this->billing['country']) . "&";
    $query .= "phone=" . urlencode($this->billing['phone']) . "&";
    $query .= "fax=" . urlencode($this->billing['fax']) . "&";
    $query .= "email=" . urlencode($this->billing['email']) . "&";
    $query .= "website=" . urlencode($this->billing['website']) . "&";
    return $this->_doPost($query);
  }

  function doCapture($transactionid, $amount =0) {

    $query  = "";
    // Login Information
    $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
    // Transaction Information
    $query .= "transactionid=" . urlencode($transactionid) . "&";
    if ($amount>0) {
        $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
    }
    $query .= "type=capture";
    return $this->_doPost($query);
  }

  function doVoid($transactionid) {

    $query  = "";
    // Login Information
    $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
    // Transaction Information
    $query .= "transactionid=" . urlencode($transactionid) . "&";
    $query .= "type=void";
    return $this->_doPost($query);
  }

  function doRefund($transactionid, $amount = 0) {

    $query  = "";
    // Login Information
    $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
    // Transaction Information
    $query .= "transactionid=" . urlencode($transactionid) . "&";
    if ($amount>0) {
        $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
    }
    $query .= "type=refund";
    return $this->_doPost($query);
  }

  function _doPost($query) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://secure.nmi.com/api/transact.php");
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_POST, 1);

    if (!($data = curl_exec($ch))) {
        return ERROR;
    }
    curl_close($ch);
    unset($ch);
   // print "\n$data\n";
    $data = explode("&",$data);
    for($i=0;$i<count($data);$i++) {
        $rdata = explode("=",$data[$i]);
        $this->responses[$rdata[0]] = $rdata[1];
    }

    return $this->responses['response'];
  }

  public function Response()
  {
      
      return isset($this->responses['response']) ? $this->responses['responsetext'] : null;
  }

}

