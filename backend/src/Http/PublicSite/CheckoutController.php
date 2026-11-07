<?php

namespace Piccolo\Http\PublicSite;

final class CheckoutController
{
    public function handle(): void
    {
        \piccolo_start_session();

        require \piccolo_path('src/Core/database.php');
        require \piccolo_path('src/Http/PublicSite/confirmar_pedido_controller.php');

        \piccolo_render(PICCOLO_VIEWS_CUSTOMER . '/views/confirmar_pedido.view.php');
    }
}
