<?php

namespace App\Services\Inventory;

use DomainException;

// El mensaje se muestra tal cual a Charlotte
class InsufficientStockException extends DomainException {}
