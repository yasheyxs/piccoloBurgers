<?php

namespace Piccolo\Http\PublicSite;

final class HomeController
{
    public function handle(): void
    {
        \piccolo_start_session();

        require \piccolo_path('src/Core/database.php');
        require \piccolo_path('src/Http/PublicSite/index_controller.php');

        \piccolo_render(PICCOLO_VIEWS_CUSTOMER . '/views/index.view.php');
    }
}
