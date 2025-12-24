<?php

namespace App\Exceptions\PaidLeave;

use App\Exceptions\DomainException;

class InsufficientPaidLeaveException extends DomainException
{
  public function __construct(
    public readonly float $availableDays
  ) {
    parent::__construct(
      "有給は残り{$availableDays}日しかありません。日数を調整してください。）"
    );
  }
}
