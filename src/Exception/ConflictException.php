<?php

declare(strict_types=1);

namespace Pidgely\Exception;

/**
 * 409: for example an idempotency key reused with a different body.
 */
class ConflictException extends PidgelyException {}
