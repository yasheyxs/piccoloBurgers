<?php

namespace Piccolo\Http\PublicSite;

final class HomeController
{
    public function handle(): void
    {
        \piccolo_start_session();

        require \piccolo_path('admin/bd.php');
        require \piccolo_path('app/Http/PublicSite/index_controller.php');

        \piccolo_render(PICCOLO_FRONTEND_CUSTOMER . '/views/index.view.php');
    }
}
