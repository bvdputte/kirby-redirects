<?php

return [
    [
        // Get all autoredirects
        'pattern' => 'kirbyredirects/all',
        'action' => function() {
            // Restrict unauthenticated access
            if (!kirby()->user()) go('error', 404);

            $redirector = bvdputte\redirects\AutoRedirects::singleton();

            $redirects = $redirector->getAll();

            return $redirector->formatForPanel($redirects);
        }
    ],
    [
        // Delete the redirect for a given redirectID, passed via the request body
        'pattern' => 'kirbyredirects/delete',
        'action' => function() {
            // Restrict unauthenticated access
            if (!kirby()->user()) go('error', 404);

            $request = kirby()->request()->body()->data();
            $redirector = bvdputte\redirects\AutoRedirects::singleton();

            $redirects = $redirector->deleteRedirect($request['redirectId']);
            if ($redirects == false) {
                throw new LogicException('Redirect delete failed.');
            }

            return [
                'errors' => false,
                'redirects' => $redirector->formatForPanel($redirects)
            ];
        },
        'method' => 'DELETE'
    ],
];
