<?php

namespace App\Exceptions\PaidLeave;

use App\Exceptions\DomainException;

class ZeroRequestedDaysException extends DomainException
{
  public function __construct() {
    parent::__construct(
      "申請された日数が0日です。1日以上の有給を申請してください。"
    );
  }
}
