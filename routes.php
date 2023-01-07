<?php

return [
    [
        // Get all autoredirects for a given page ID
        'pattern' => 'kirbyredirects/allForId/(:any)',
        'action' => function($pageId) {
            // Restrict unauthenticated access
            if (!kirby()->user()) go('error', 404);

            ray(bvdputte\redirects\AutoRedirects::singleton()->getAllTo($pageId));
            die;
        }
    ],
    [
        // Delete the redirect for a given "from" slug & page ID
        'pattern' => 'kirbyredirects/delete/(:any)/(:any)',
        'action' => function($from, $pageId) {
            // Restrict unauthenticated access
            if (!kirby()->user()) go('error', 404);

            // Slashes are replaced in $from by pipes ("%7C")
            ray(bvdputte\redirects\AutoRedirects::singleton()->delete(str_replace('%7C', '/', $from), $pageId));
            die;
        }
    ]
];
