<?php
require_once __DIR__ . '/../app/Core/bootstrap.php';

(new Piccolo\Http\PublicSite\CartController())->handle();
