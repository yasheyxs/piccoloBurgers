<?php
require_once __DIR__ . '/../src/Core/bootstrap.php';

(new Piccolo\Http\PublicSite\CartController())->handle();
