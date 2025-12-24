<?php

namespace App\Exceptions\PaidLeave;

use App\Exceptions\DomainException;

class InvalidRequestStatusException extends DomainException
{
  private const STATUS_LABELS = [
    'pending' => '申請中',
    'approved' => '承認済み',
    'rejected' => '却下済み',
  ];

  public function __construct(string $status)
  {
    $label = self::STATUS_LABELS[$status] ?? $status;

    parent::__construct(
      "この申請は処理できません。すでに{$label}です。"
    );
  }
}
