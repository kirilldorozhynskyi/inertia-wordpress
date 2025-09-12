<?php

if (!function_exists('bb_inject_inertia')) {
    function bb_inject_inertia(string $id = 'app', string $classes = '')
    {
        global $bb_inertia_page;

        if (!isset($bb_inertia_page)) {
            return;
        }

        $classes = !empty($classes)
            ? 'class="' . $classes . '"'
            : '';

        $page = wp_json_encode($bb_inertia_page);
        $content = '';

        if(
            // Not an AJAX request
            !(defined('DOING_AJAX') && DOING_AJAX)
            
            // SSR entry point exists
            && file_exists(get_template_directory().'/build/ssr/ssr.js')) 
        {
            // Optionally skip SSR via filter or in admin
            $ssr_enabled  = apply_filters('bb_inertia_enable_ssr', true);
            $is_admin     = function_exists('is_admin') ? is_admin() : false;

            if ($ssr_enabled && !$is_admin) {
                $ssr_url            = apply_filters('bb_inertia_ssr_url', 'http://127.0.0.1:13714/render');
                $connect_timeout_ms = (int) apply_filters('bb_inertia_ssr_connect_timeout_ms', 75);
                $timeout_ms         = (int) apply_filters('bb_inertia_ssr_timeout_ms', 150);

                $curl = curl_init($ssr_url);

                if ($curl !== false) {
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $page);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

                    if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT_MS, max(1, $connect_timeout_ms));
                    } else {
                        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, max(1, (int) ceil($connect_timeout_ms / 1000)));
                    }

                    if (defined('CURLOPT_TIMEOUT_MS')) {
                        curl_setopt($curl, CURLOPT_TIMEOUT_MS, max(1, $timeout_ms));
                    } else {
                        curl_setopt($curl, CURLOPT_TIMEOUT, max(1, (int) ceil($timeout_ms / 1000)));
                    }

                    $raw = curl_exec($curl);
                    $err = curl_errno($curl);
                    curl_close($curl);

                    if (!$err && $raw) {
                        $decoded = json_decode($raw);
                        if ($decoded && isset($decoded->body)) {
                            echo $decoded->body;
                            return;
                        }
                    }
                }
            }
        }

        $page = htmlspecialchars(
            $page,
            ENT_QUOTES,
            'UTF-8',
            true
        );        

        echo "<div id=\"{$id}\" {$classes} data-page=\"{$page}\">$content</div>";
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}
