<?php



if (!function_exists('getCategoryIcon')) {
    function getCategoryIcon($categoryId) {
        return match ($categoryId) {
            1 => 'fa-plane',
            2 => 'fa-building',
            3 => 'fa-globe',
            default => 'fa-question-circle',
        };
    }
}
