<?php

// This file is replaced by comprehensive feature tests in other files.
// The tests are now organized into specific workflow tests:
// - DashboardWorkflowTest.php
// - InvoiceWorkflowTest.php
// - EndToEndWorkflowTest.php
// - Controllers/PositionControllerTest.php
// - Controllers/PayPalControllerTest.php

it('redirects root path correctly based on authentication', function () {
    // Unauthenticated users should be redirected to login
    $response = $this->get('/');
    $response->assertRedirect(route('login'));
});
