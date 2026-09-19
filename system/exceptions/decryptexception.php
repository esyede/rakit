<?php

namespace System\Exceptions;

defined('DS') or exit('No direct access.');

/**
 * Thrown when a ciphertext cannot be read back.
 *
 * The value was not produced by this application: a rotated key, or a payload
 * written by something else. Routine rather than a fault, so callers reading
 * untrusted input are expected to catch it and fall back.
 */
class DecryptException extends \Exception
{
}
