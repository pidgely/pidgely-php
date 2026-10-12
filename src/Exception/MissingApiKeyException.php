<?php

declare(strict_types=1);

namespace Pidgely\Exception;

/**
 * No API key was given and PIDGELY_API_KEY is not set.
 */
class MissingApiKeyException extends PidgelyException {}
