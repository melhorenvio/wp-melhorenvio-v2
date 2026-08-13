<?php

// Compatibility shim: upgrade from 2.x maps Services/ without legacy/ prefix.
// This file ensures the old Composer autoloader can resolve the class during upgrade.
require_once __DIR__ . '/../legacy/Services/ClearDataStored.php';