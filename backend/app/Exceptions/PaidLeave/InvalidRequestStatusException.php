<?php

namespace App\Exceptions\PaidLeave;

use App\Exceptions\DomainException;

class InvalidRequestStatusException extends DomainException
{
  public function __construct(string $status)
  {
    parent::__construct(
      "この申請は処理できません。すでに{$status}されています。"
    );
  }
}