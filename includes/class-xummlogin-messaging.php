<?php
class Xummlogin_Messaging{

  // Empty list to start with
  private $messages = [];

	public function __construct() {

	}

  public function add($type, $feature, $code, $message = ''){
    $this->messages[] = [
      'type'    => $type,
      'feature' => $feature,
      'code'    => $code,
      'message' => $message,
    ];
  }

  public function get_messages(){
    return (array)$this->messages;
  }

}
?>