<?php

namespace Piccolo\Http\PublicSite;

final class CartController
{
    public function handle(): void
    {
        \piccolo_start_session();

        require \piccolo_path('src/Core/database.php');
        require \piccolo_path('src/Http/PublicSite/carrito_controller.php');

        \piccolo_render(PICCOLO_VIEWS_CUSTOMER . '/views/carrito.view.php');
    }
}
