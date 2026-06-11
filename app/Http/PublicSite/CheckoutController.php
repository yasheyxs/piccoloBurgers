<?php

namespace Piccolo\Http\PublicSite;

final class CheckoutController
{
    public function handle(): void
    {
        \piccolo_start_session();

        require \piccolo_path('admin/bd.php');
        require \piccolo_path('app/Http/PublicSite/confirmar_pedido_controller.php');

        \piccolo_render(PICCOLO_FRONTEND_CUSTOMER . '/views/confirmar_pedido.view.php');
    }
}
