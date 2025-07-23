# Automated Tests Documentation

This application now includes comprehensive automated tests covering all major functionality.

## Test Structure

### Unit Tests (`tests/Unit/`)

Unit tests focus on testing individual components in isolation:

- **Models/PositionTest.php** - Tests the Position model calculations, relationships, and scopes
- **Models/InvoiceTest.php** - Tests the Invoice model and Lexoffice integration methods
- **Models/CustomerTest.php** - Tests the Customer model and Lexoffice contact conversion
- **Models/UserTest.php** - Tests the User model role methods and relationships

### Feature Tests (`tests/Feature/`)

Feature tests cover complete workflows and user interactions:

- **DashboardWorkflowTest.php** - Tests authentication, dashboard access, and user permissions
- **InvoiceWorkflowTest.php** - Tests invoice management, payment tracking, and position billing
- **EndToEndWorkflowTest.php** - Tests complete business workflows from customer creation to payment
- **Controllers/PositionControllerTest.php** - Tests the PositionController CRUD operations and authorization
- **Controllers/PayPalControllerTest.php** - Tests the PayPal payment integration

## Test Coverage

The tests cover:

### Core Business Logic
- Position calculations (totals, discounts, taxes)
- Invoice generation from Lexoffice data
- Customer management and user account creation
- Position-to-invoice billing workflow

### Security & Authorization
- User role-based access control
- Admin vs customer permission separation
- Protection of billed positions from modification
- Session management for payments

### Integration Points
- Lexoffice API data conversion
- PayPal payment processing
- Database relationships and constraints
- Web request/response handling

### Edge Cases
- Missing or invalid data handling
- Payment failures and cancellations
- Network timeouts and API errors
- Concurrent access scenarios

## Running Tests

### Prerequisites
1. Install PHP dependencies: `composer install`
2. Set up test environment: copy `.env.example` to `.env`
3. Configure test database (SQLite in-memory by default)

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suites
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only  
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Unit/Models/PositionTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

## Test Factories

Database factories are available for creating test data:

- **UserFactory** - Creates users with different roles
- **CustomerFactory** - Creates customers with Lexoffice integration
- **PositionFactory** - Creates positions with various states (billed/unbilled)
- **InvoiceFactory** - Creates invoices with different payment states

Example usage:
```php
$customer = Customer::factory()->create();
$position = Position::factory()->unbilled()->create(['customer_id' => $customer->id]);
$invoice = Invoice::factory()->open()->create(['customer_id' => $customer->id]);
```

## Test Philosophy

These tests follow Laravel and Pest PHP best practices:

1. **Descriptive Names** - Test names clearly describe what is being tested
2. **Arrange-Act-Assert** - Tests are structured with clear setup, execution, and verification
3. **Isolation** - Each test is independent and can run in any order
4. **Real Scenarios** - Tests simulate actual user workflows and business processes
5. **Edge Case Coverage** - Tests handle both happy path and error conditions

## Continuous Integration

These tests are designed to run in CI/CD pipelines and provide:

- Fast feedback on code changes
- Regression detection
- Documentation of expected behavior
- Confidence in deployments

The test suite typically completes in under 30 seconds and provides comprehensive coverage of the application's critical functionality.