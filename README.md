# LumoSolutions - Actionable

A Laravel package that provides a clean, elegant way to create dispatchable and runnable actions with built-in array conversion capabilities. Simplify your Laravel application's business logic with reusable, testable action classes.

## Features

- **Runnable Actions**: Execute actions synchronously with dependency injection support
- **Dispatchable Actions**: Queue actions for asynchronous execution
- **Array Conversion**: Convert objects to/from arrays with attribute-based customization
- **Artisan Commands**: Generate action and DTO stubs quickly
- **Flexible Attributes**: Control serialization behavior with custom attributes

## Installation

Install the package via Composer:

```bash
composer require lumo-solutions/actionable
```

## Quick Start

### Creating a Basic Action

```php
<?php

namespace App\Actions;

use LumoSolutions\Actionable\Traits\IsRunnable;

class SendWelcomeEmail
{
    use IsRunnable;

    public function handle(string $email, string $name): void
    {
        // Your action logic here
        Mail::to($email)->send(new WelcomeEmail($name));
    }
}
```

### Running the Action

```php
// Execute the action synchronously
SendWelcomeEmail::run('user@example.com', 'John Doe');
```

### Creating a Dispatchable Action

```php
<?php

namespace App\Actions;

use LumoSolutions\Actionable\Traits\IsRunnable;
use LumoSolutions\Actionable\Traits\IsDispatchable;

class ProcessLargeDataset
{
    use IsRunnable, IsDispatchable;

    public function handle(array $data): void
    {
        // Process large dataset
        foreach ($data as $item) {
            // Heavy processing logic
        }
    }
}
```

### Dispatching Actions to Queue

```php
// Dispatch to default queue
ProcessLargeDataset::dispatch($largeDataArray);

// Dispatch to specific queue
ProcessLargeDataset::dispatchOn('heavy-processing', $largeDataArray);
```

## Creating DTOs with Array Conversion

### Basic DTO

```php
<?php

namespace App\DTOs;

use LumoSolutions\Actionable\Traits\ArrayConvertible;

class UserData
{
    use ArrayConvertible;

    public function __construct(
        public string $name,
        public string $email,
        public int $age
    ) {}
}
```

### Using the DTO

```php
// Create from array
$userData = UserData::fromArray([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
]);

// Convert to array
$array = $userData->toArray();
```

## Attributes Reference

The package provides several attributes to customize array conversion behavior. All attributes can be applied to constructor parameters or class properties.

### #[FieldName] - Custom Field Mapping

Map property names to different array keys:

```php
<?php

use LumoSolutions\Actionable\Attributes\FieldName;

class ApiResponse
{
    use ArrayConvertible;

    public function __construct(
        #[FieldName('user_id')]
        public int $userId,
        
        #[FieldName('full_name')]
        public string $fullName,
        
        #[FieldName('email_address')]
        public string $email
    ) {}
}

// Usage
$response = ApiResponse::fromArray([
    'user_id' => 123,
    'full_name' => 'John Doe',
    'email_address' => 'john@example.com'
]);

$array = $response->toArray();
// Returns: ['user_id' => 123, 'full_name' => 'John Doe', 'email_address' => 'john@example.com']
```

### #[DateFormat] - Date Serialization Control

Control how DateTime objects are formatted during array conversion:

```php
<?php

use LumoSolutions\Actionable\Attributes\DateFormat;

class EventData
{
    use ArrayConvertible;

    public function __construct(
        public string $title,
        
        #[DateFormat('Y-m-d')]
        public DateTime $eventDate,
        
        #[DateFormat('H:i:s')]
        public DateTime $startTime,
        
        #[DateFormat('c')] // ISO 8601 format
        public DateTime $createdAt,
        
        public DateTime $updatedAt // Uses default format 'Y-m-d H:i:s'
    ) {}
}

// Usage
$event = new EventData(
    title: 'Conference 2024',
    eventDate: new DateTime('2024-06-15'),
    startTime: new DateTime('2024-06-15 09:00:00'),
    createdAt: new DateTime(),
    updatedAt: new DateTime()
);

$array = $event->toArray();
// Returns formatted dates according to their attributes
```

### #[ArrayOf] - Nested Object Arrays

Handle arrays containing objects of a specific class:

```php
<?php

use LumoSolutions\Actionable\Attributes\ArrayOf;

class OrderItem
{
    use ArrayConvertible;
    
    public function __construct(
        public string $name,
        public int $quantity,
        public float $price
    ) {}
}

class Order
{
    use ArrayConvertible;

    public function __construct(
        public string $orderNumber,
        
        #[ArrayOf(OrderItem::class)]
        public array $items,
        
        #[ArrayOf(OrderItem::class)]
        public array $bundleItems = []
    ) {}
}

// Usage
$order = Order::fromArray([
    'orderNumber' => 'ORD-001',
    'items' => [
        ['name' => 'Laptop', 'quantity' => 1, 'price' => 999.99],
        ['name' => 'Mouse', 'quantity' => 2, 'price' => 25.00]
    ],
    'bundleItems' => [
        ['name' => 'Cable', 'quantity' => 1, 'price' => 15.00]
    ]
]);

// The arrays are automatically converted to OrderItem objects
echo $order->items[0]->name; // 'Laptop'
```

### #[Ignore] - Exclude from Conversion

Exclude sensitive or computed properties from array conversion:

```php
<?php

use LumoSolutions\Actionable\Attributes\Ignore;

class UserAccount
{
    use ArrayConvertible;

    public function __construct(
        public string $username,
        public string $email,
        
        #[Ignore]
        public string $password,
        
        #[Ignore]
        public string $apiKey,
        
        public DateTime $createdAt
    ) {}
    
    #[Ignore]
    public function getFullProfile(): array
    {
        // This method won't be included in array conversion
        return ['username' => $this->username, 'email' => $this->email];
    }
}

// Usage
$user = new UserAccount(
    username: 'johndoe',
    email: 'john@example.com',
    password: 'secret123',
    apiKey: 'api_key_here',
    createdAt: new DateTime()
);

$array = $user->toArray();
// Returns: ['username' => 'johndoe', 'email' => 'john@example.com', 'createdAt' => '2024-01-01 12:00:00']
// Password and apiKey are excluded
```

### Combining Attributes

You can combine multiple attributes on the same property:

```php
<?php

class UserProfile
{
    use ArrayConvertible;

    public function __construct(
        #[FieldName('user_id')]
        public int $userId,
        
        #[FieldName('birth_date')]
        #[DateFormat('Y-m-d')]
        public DateTime $birthDate,
        
        #[FieldName('contact_emails')]
        #[ArrayOf(EmailAddress::class)]  
        public array $emails,
        
        #[Ignore]
        public string $internalNotes
    ) {}
}
```

### Real-World Example

Here's a comprehensive example showing all attributes working together:

```php
<?php

class CustomerOrder
{
    use ArrayConvertible;

    public function __construct(
        #[FieldName('order_id')]
        public string $orderId,
        
        #[FieldName('customer_info')]
        public CustomerInfo $customer,
        
        #[FieldName('order_items')]
        #[ArrayOf(OrderItem::class)]
        public array $items,
        
        #[FieldName('order_date')]
        #[DateFormat('Y-m-d')]
        public DateTime $orderDate,
        
        #[FieldName('delivery_time')]
        #[DateFormat('H:i')]
        public DateTime $estimatedDelivery,
        
        #[Ignore]
        public string $internalNotes,
        
        #[Ignore]
        public float $internalCost
    ) {}
}

// This creates a clean API response while protecting sensitive data
$order = CustomerOrder::fromArray($apiData);
$response = $order->toArray(); // Clean, formatted output ready for API
```

## Artisan Commands

The package provides convenient Artisan commands to generate boilerplate code:

### Generate Action Classes

```bash
# Generate basic runnable action
php artisan make:action SendNotification

# Generate dispatchable action (can be queued)
php artisan make:action ProcessPayment --dispatchable

# Generate invokable action
php artisan make:action CalculateTotal --invokable
```

The `make:action` command supports the following options:
- `--dispatchable` - Adds the `IsDispatchable` trait for queueing support
- `--invokable` - Creates an invokable action (uses `action.invokable.stub`)

### Generate DTO Classes

```bash
# Generate basic DTO with array conversion capabilities
php artisan make:dto UserRegistrationData
```

### Custom Stubs

You can publish and customize the stubs used by the commands by creating them in your application's `stubs/lumosolutions/actionable/` directory. The package will automatically use your custom stubs if they exist:

- `action.stub` - Basic action template
- `action.dispatchable.stub` - Dispatchable action template
- `action.invokable.stub` - Invokable action template
- `dto.stub` - DTO template

Example custom stub location:
```
stubs/
└── lumosolutions/
    └── actionable/
        ├── action.stub
        ├── action.dispatchable.stub
        ├── action.invokable.stub
        └── dto.stub
```

## Complete Example

Here's a complete example showing how to use actions and DTOs together:

```php
<?php

// DTO for user registration data
class UserRegistrationData
{
    use ArrayConvertible;

    public function __construct(
        #[FieldName('full_name')]
        public string $fullName,
        
        public string $email,
        
        #[DateFormat('Y-m-d')]
        public DateTime $birthDate,
        
        #[Ignore]
        public string $password
    ) {}
}

// Action to process user registration
class RegisterUser
{
    use IsRunnable, IsDispatchable;

    public function handle(UserRegistrationData $userData): User
    {
        // Validate and create user
        $user = User::create([
            'name' => $userData->fullName,
            'email' => $userData->email,
            'birth_date' => $userData->birthDate,
            'password' => Hash::make($userData->password)
        ]);

        // Send welcome email asynchronously
        SendWelcomeEmail::dispatch($user->email, $user->name);

        return $user;
    }
}

// Usage
$registrationData = UserRegistrationData::fromArray($request->all());
$user = RegisterUser::run($registrationData);
```

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

If you discover any security vulnerabilities or bugs, please create an issue on GitHub.
