<?php

use App\Mcp\Servers\AttendanceServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/attendance', AttendanceServer::class)
    ->middleware(['auth:sanctum', 'throttle:mcp-token']);
