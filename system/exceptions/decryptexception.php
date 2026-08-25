<?php

namespace System\Exceptions;

defined('DS') or exit('No direct access.');

/**
 * Thrown when a ciphertext cannot be read back.
 *
 * This means the value was not produced by this application: the key has
 * been rotated, or the payload was written by something else entirely. It
 * is a routine condition rather than a server fault, so callers that read
 * untrusted input are expected to catch it and fall back.
 */
class DecryptException extends \Exception
{
    //
}
