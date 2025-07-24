<?php

if (!function_exists('greetUser')) {
    /**
     * Generate a time-based greeting with optional user name
     *
     * @param string|null $name
     * @return string
     */
    function greetUser($name = null)
    {
        $hour = now()->hour;

        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Good Morning';
        } elseif ($hour >= 12 && $hour < 17) {
            $greeting = 'Good Afternoon';
        } else {
            $greeting = 'Good Evening';
        }

        return $name ? "$greeting, $name!" : "$greeting!";
    }
}
