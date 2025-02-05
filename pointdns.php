<?php

class PointDNS {

  function __construct($username, $apitoken, $timeout=10) {
    $this->username = $username;
    $this->apitoken = $apitoken;
    $this->timeout = $timeout;
  }

  function paramsValidate($params, $list) {
    //params validate
    foreach ($list as $param) {
      if (!array_key_exists($param, $params)) {
        return array(1, 'param not found: '.$param, null);
      } elseif (!is_int($params[$param])) {
        return array(1, 'param is not an int type: '. $params[$param], null);
      }
    }
    return array(0);
  }

  function escape($escape, $data) {
    //escape from nested structures received from api
    if (is_array($data)) {
      if (array_key_exists('list_escape', $escape)) {
        $tmp = array();
        foreach($data as $key=>$value) {
          array_push($tmp, $value[$escape['list_escape']]);
        }
        $data = $tmp;
      } elseif (array_key_exists('escape', $escape)) {
        $data = $data[$escape['escape']];
      }
    }
    return $data;
  }

  //zones

  function getZones($params=null) {

    $query = '';
    if (is_array($params) && array_key_exists('group', $params)) {
      $query = '?group=' . $params['group'];
    }

    $path = '/zones' . $query;
    $ret = $this->call(200, 'GET', $path);
    return array( $ret[0], $ret[1], $this->escape(array('list_escape'=> 'zone'), $ret[2]) );
  }

  function addZone($fields) {

    $path = '/zones';
    $ret = $this->call(201, 'POST', $path, array('zone' => $fields));
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone'), $ret[2]) );
  }

  function updateZone($params, $fields) {

    if ( ($err = $this->paramsValidate($params, array('zone_id'))) && $err[0]) {
      return $err;
    }
    $path = '/zones/' . $params['zone_id'];
    $ret = $this->call(202, 'PUT', $path, array('zone' => $fields));
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone'), $ret[2]) );
  }

  function getZone($params) {

    if ( ($err = $this->paramsValidate($params, array('zone_id'))) && $err[0]) {
      return $err;
    }
    $path = '/zones/' . $params['zone_id'];
    $ret = $this->call(200, 'GET', $path);
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone'), $ret[2]) );
  }

  function deleteZone($params) {

    if ( ($err = $this->paramsValidate($params, array('zone_id'))) && $err[0]) {
      return $err;
    }
    $path = '/zones/' . $params['zone_id'];
    $ret = $this->call(202, 'DELETE', $path);
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone'), $ret[2]) );
  }

  //records

  function getRecords($params) {

    if ( ($err = $this->paramsValidate($params, array('zone_id'))) && $err[0]) {
      return $err;
    }
    $path = '/zones/' . $params['zone_id'] . '/records';
    $ret = $this->call(200, 'GET', $path);
    return array( $ret[0], $ret[1], $this->escape(array('list_escape'=> 'zone_record'), $ret[2]) );
  }

  function addRecord($params, $fields) {

    if ( ($err = $this->paramsValidate($params, array('zone_id'))) && $err[0]) {
      return $err;
    }
    $path =  '/zones/' . $params['zone_id'] . '/records';
    $ret = $this->call(201, 'POST', $path, array('zone_record' => $fields));
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone_record'), $ret[2]) );
  }

  function updateRecord($params, $fields) {

    if ( ($err = $this->paramsValidate($params, array('zone_id', 'record_id'))) && $err[0]) {
      return $err;
    }
    $path = '/zones/' . $params['zone_id'] . '/records/' . $params['record_id'];
    $ret = $this->call(202, 'PUT', $path, array('zone_record' => $fields));
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone_record'), $ret[2]) );
  }

  function getRecord($params) {

    if ( ($err = $this->paramsValidate($params, array('zone_id', 'record_id'))) && $err[0]) {
      return $err;
    }
    $path =  '/zones/' . $params['zone_id'] . '/records/' . $params['record_id'];
    $ret = $this->call(200, 'GET', $path, array('zone_record' => $fields));
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone_record'), $ret[2]) );
  }

  function deleteRecord($params) {

    if ( ($err = $this->paramsValidate($params, array('zone_id', 'record_id'))) && $err[0]) {
      return $err;
    }
    $path =  '/zones/' . $params['zone_id'] . '/records/' . $params['record_id'];
    $ret = $this->call(202, 'DELETE', $path, array('zone_record' => $fields));
    return array( $ret[0], $ret[1], $this->escape(array('escape'=> 'zone_record'), $ret[2]) );
  }




  function call($status, $method, $path, $fields=null){
    //prepare & execute https request
    $headers = array(
        'Accept: application/json',
        'User-Agent: PHP-pointDNS',
    );

    $process = curl_init();

    curl_setopt($process, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($process, CURLOPT_URL, 'https://api.pointhq.com' . $path);
    curl_setopt($process, CURLOPT_USERPWD, $this->username . ":" . $this->apitoken);
    curl_setopt($process, CURLOPT_TIMEOUT, $this->timeout);

    if (preg_match("/(POST|PUT)/i", $method)){
      $fields_string = json_encode($fields);
      curl_setopt($process,CURLOPT_POSTFIELDS, $fields_string);
      array_push($headers, 'Content-Type: application/json');
      array_push($headers, 'Content-Length: ' . strlen($fields_string));
    }
    curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);

    $content = trim(curl_exec($process) );
    $code = curl_getinfo ($process, CURLINFO_HTTP_CODE);
    $err = curl_errno ($process);
    $errmsg = curl_error ($process);

    curl_close($process);

    //check response code
    if ($status != $code) {
      $err = $code;
      $errmsg = 'HTTP response code error, expected:'.$status.' received:'.$code;
      return array($err, $errmsg, $content);
    }

    //decode response
    $result = json_decode($content, true);
    if (!is_array($result)) {
      $err = json_last_error();
      $errmsg = 'JSON cannot be decoded: '.json_last_error();
    }

    return array($err, $errmsg, $result);
  }

}

?>
