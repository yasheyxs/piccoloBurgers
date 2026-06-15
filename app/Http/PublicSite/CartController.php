<?php

namespace Piccolo\Http\PublicSite;

final class CartController
{
    public function handle(): void
    {
        \piccolo_start_session();

        require \piccolo_path('admin/bd.php');
        require \piccolo_path('app/Http/PublicSite/carrito_controller.php');

        \piccolo_render(PICCOLO_FRONTEND_CUSTOMER . '/views/carrito.view.php');
    }
}
