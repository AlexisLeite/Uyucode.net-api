<?php

class tttNode
{
  public $type = "node";

  public function __construct($checkResult, public $name)
  {
    if ($checkResult === 0) {
      $this->status = "winner";
    } else if ($checkResult === 1) {
      $this->status = "loser";
    } else if ($checkResult === -1) {
      $this->status = "draw";
    }
  }
}
