<?php

namespace Koselig\Hashing;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;

/**
 * Gives an interface to hash Wordpress passwords from
 * within the Laravel environment.
 *
 * @author Jordan Doyle <jordan@doyle.wf>
 */
class WordpressHasher implements HasherContract
{
    /**
     * Hash the given value.
     *
     * @param string $value
     * @param array  $options
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    public function make($value, array $options = [])
    {
        return wp_hash_password($value);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string $hashedValue
     * @param array  $options
     *
     * @return bool
     */
    public function check($value, $hashedValue, array $options = [])
    {
        return wp_check_password($value, $hashedValue, isset($options['user_id']) ? $options['user_id'] : '');
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue
     * @param array  $options
     *
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = [])
    {
        // if the hashed value is md5 it needs rehashing.
        return strlen($hashedValue) <= 32;
    }
}
