<?php

if (!function_exists('purify')) {
    function purify(string $html): string
    {
        static $purifier = null;

        if ($purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed',
                'h1,h2,h3,h4,h5,h6,p,br,strong,em,b,i,u,s,' .
                'ul,ol,li,dl,dt,dd,' .
                'a[href|title|target],blockquote,pre,code,' .
                'table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan],' .
                'span,div,hr,sup,sub'
            );
            $config->set('HTML.TargetBlank', true);
            $config->set('Attr.AllowedRel', 'noopener noreferrer');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('Cache.SerializerPath', storage_path('framework/cache/htmlpurifier'));
            $purifier = new HTMLPurifier($config);
        }

        return $purifier->purify($html);
    }
}
